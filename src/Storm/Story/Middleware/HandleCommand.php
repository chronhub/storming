<?php

declare(strict_types=1);

namespace Storm\Story\Middleware;

use Exception;
use ReflectionClass;
use Storm\Chronicler\Tracker\ListenerOnce;
use Storm\Chronicler\Tracker\StreamDecoratorOnAppend;
use Storm\Contract\Chronicler\Chronicler;
use Storm\Contract\Chronicler\EventableChronicler;
use Storm\Contract\Chronicler\EventableTransactionalChronicler;
use Storm\Contract\Chronicler\TransactionalChronicler;
use Storm\Message\DomainCommand;
use Storm\Story\Attribute\Transactional;
use Storm\Story\Draft;
use Storm\Story\Exception\StoryViolation;
use Storm\Story\Support\CausationCommandDecorator;
use Throwable;

use function count;
use function get_class;
use function sprintf;

final class HandleCommand
{
    private Chronicler|EventableChronicler|EventableTransactionalChronicler $chronicler;

    public function __construct(Chronicler $chronicler)
    {
        $this->chronicler = $chronicler;
    }

    /**
     * Handles the command.
     *
     * @throws Throwable
     */
    public function __invoke(Draft $draft, callable $next): Draft
    {
        [$command, $handler] = $this->checkCommandAndGetSingleHandler($draft);

        try {
            $this->handleCommand($command, $handler);
        } catch (Exception $exception) {
            $draft->job?->fail($exception);

            throw $exception;
        }

        $draft->markHandled();

        return $next($draft);
    }

    /**
     * Handles the command with transaction.
     *
     * @throws Throwable
     */
    private function handleCommand(DomainCommand $command, callable $commandHandler): void
    {
        if ($this->isTransactionalCommand($command)) {
            $this->chronicler->beginTransaction();

            try {
                $this->callCommand($command, $commandHandler);

                $this->chronicler->commitTransaction(); // @phpstan-ignore-line
            } catch (Exception $exception) {
                $this->chronicler->rollbackTransaction();

                throw $exception;
            }
        } else {
            $this->callCommand($command, $commandHandler);
        }
    }

    /**
     * Handles the command without transaction.
     *
     * @throws Throwable
     */
    private function callCommand(DomainCommand $command, callable $commandHandler): void
    {
        $listener = null;

        try {
            if ($this->chronicler instanceof EventableChronicler) {
                $listener = $this->chronicler->subscribeOnce(
                    EventableChronicler::APPEND_STREAM,
                    new StreamDecoratorOnAppend(new CausationCommandDecorator($command)),
                    100
                );
            }

            $commandHandler($command);
        } catch (Exception $exception) {
            if ($this->chronicler instanceof EventableChronicler && $listener instanceof ListenerOnce) {
                $this->chronicler->unsubscribe($listener);
            }

            throw $exception;
        }
    }

    /**
     * Checks and returns the command and handler.
     *
     * @return array{DomainCommand, callable}
     *
     * @throws StoryViolation when the command is not a DomainCommand
     * @throws StoryViolation when the command has not single handler
     */
    private function checkCommandAndGetSingleHandler(Draft $draft): array
    {
        $command = $draft->getMessage();

        if (! $command instanceof DomainCommand) {
            throw new StoryViolation(sprintf('Expected a DomainCommand, got %s', get_class($command)));
        }

        $handlers = $draft->getOnceHandlers();

        if (count($handlers) !== 1) {
            throw new StoryViolation(sprintf('Story Command only supports single handler for command: %s', get_class($command)));
        }

        return [$command, $handlers[0]];
    }

    /**
     * Checks if the command and the event store support transactional.
     *
     * checkMe till no other attribute on command, we keep the reflection here
     *  it can interfere with process manager or saga, we will care when there is a need
     *
     * @phpstan-assert-if-true TransactionalChronicler $this->chronicler
     */
    private function isTransactionalCommand(DomainCommand $command): bool
    {
        if (! $this->chronicler instanceof TransactionalChronicler) {
            return false;
        }

        $reflection = new ReflectionClass($command);

        return null !== ($reflection->getAttributes(Transactional::class)[0] ?? null);
    }
}
