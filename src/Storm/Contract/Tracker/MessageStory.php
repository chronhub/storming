<?php

declare(strict_types=1);

namespace Storm\Contract\Tracker;

use Generator;
use React\Promise\PromiseInterface;
use Storm\Message\Message;

interface MessageStory extends Story
{
    /**
     * Set a transient message.
     */
    public function withTransientMessage(object|array $transientMessage): void;

    /**
     * Return the transient message.
     */
    public function transientMessage(): null|object|array;

    /**
     * Pull the transient message.
     * Should be done once to be replaced by a valid message instance
     */
    public function pullTransientMessage(): object|array;

    /**
     * Set valid message instance.
     */
    public function withMessage(Message $message): void;

    /**
     * Return the current message.
     */
    public function message(): Message;

    /**
     * Add message handlers.
     */
    public function withHandlers(iterable $handlers): void;

    /**
     * Yield handlers
     */
    public function handlers(): Generator;

    /**
     * Mark message handled.
     */
    public function markHandled(bool $isMessageHandled): void;

    /**
     * Check if the message has been handled
     */
    public function isHandled(): bool;

    /**
     * Set promise.
     * Available only for query messages
     */
    public function withPromise(PromiseInterface $promise): void;

    /**
     * Return promise if exists.
     */
    public function promise(): ?PromiseInterface;
}
