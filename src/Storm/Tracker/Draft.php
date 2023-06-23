<?php

declare(strict_types=1);

namespace Storm\Tracker;

use Generator;
use React\Promise\PromiseInterface;
use Storm\Contract\Tracker\MessageStory;
use Storm\Message\Message;

class Draft implements MessageStory
{
    use InteractWithStory;

    /**
     * The message instance
     */
    private ?Message $message = null;

    /**
     * Transient message
     *
     * A message factory should be responsible to produce a valid message instance
     */
    private object|array|null $transientMessage = null;

    /**
     * Message handler(s)
     *
     * @var iterable<object>
     */
    private iterable $handlers = [];

    /**
     * Is message handled
     */
    private bool $isHandled = false;

    /**
     * Promise interface available for domain query
     */
    private ?PromiseInterface $promise = null;

    public function withTransientMessage(object|array $transientMessage): void
    {
        $this->transientMessage = $transientMessage;
    }

    public function withMessage(Message $message): void
    {
        $this->message = $message;
    }

    public function withHandlers(iterable $handlers): void
    {
        $this->handlers = $handlers;
    }

    public function handlers(): Generator
    {
        yield from $this->handlers;
    }

    public function withPromise(PromiseInterface $promise): void
    {
        $this->promise = $promise;
    }

    public function markHandled(bool $isMessageHandled): void
    {
        $this->isHandled = $isMessageHandled;
    }

    public function isHandled(): bool
    {
        return $this->isHandled;
    }

    public function transientMessage(): null|object|array
    {
        return $this->transientMessage;
    }

    public function pullTransientMessage(): object|array
    {
        $transientMessage = $this->transientMessage;

        $this->transientMessage = null;

        return $transientMessage;
    }

    public function message(): Message
    {
        return $this->message;
    }

    public function promise(): ?PromiseInterface
    {
        return $this->promise;
    }
}
