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

/**
 * Command Execution and Exception Handling Strategy
 *
 * 1. Failure Handling:
 *    a. Immediate Failure: Commands fail on the first execution attempt.
 *    b. Nuanced Approach: Consider categorizing exceptions for differentiated handling.
 *
 * 2. Exception Sources:
 *    a. Domain Logic: Most exceptions from business rule violations.
 *    b. Infrastructure: Database issues, external service failures, etc.
 *    c. Concurrency: Race conditions, duplicate executions.
 *
 * 3. Retry Considerations:
 *    a. Avoid automatic retries for domain logic exceptions.
 *    b. Consider retries for transient infrastructure issues.
 *    c. Implement idempotency for safely retryable commands.
 *
 * 4. Concurrency Handling:
 *    a. Pay special attention to race conditions and duplicates.
 *    b. Consider using version-based conflict resolution strategies.
 *
 * 5. Event Sourcing Implications:
 *    a. Ensure event stream integrity and correct ordering.
 *    b. Consider compensating events for certain failure scenarios.
 *    c. Implement event publishing as part of the command handling process.
 *
 * 6. Consistency and Boundaries:
 *    a. Respect aggregate boundaries for consistency guarantees.
 *    b. Consider eventual consistency for cross-aggregate operations.
 *
 * 7. Error Recovery Strategies:
 *    a. Implement compensating actions for certain types of failures.
 *    b. Provide mechanisms for manual intervention and recovery.
 *
 * 8. Logging and Monitoring:
 *    a. Log all command failures with detailed context.
 *    b. Implement comprehensive monitoring for command execution patterns.
 *
 * 9. Performance Considerations:
 *    a. Balance between immediate failure and retry attempts.
 *    b. Consider the impact of exception handling on system performance.
 *
 * 10. Developer Responsibilities:
 *     a. Analyze and address recurring exceptions.
 *     b. Regularly review and update exception handling strategies.
 *     c. Ensure proper error reporting and alerting mechanisms are in place.
 *
 * Note: The current implementation fails immediately and throws exceptions.
 * Consider refining this approach based on specific system requirements and
 * the nature of commands being processed.
 */
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
