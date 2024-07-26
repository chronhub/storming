<?php

declare(strict_types=1);

namespace Storm\Contract\Tracker;

use Storm\Contract\Message\MessageDecorator;

interface StreamStory extends Story
{
    /**
     * set the deferred callback.
     */
    public function deferred(callable $callback): void;

    /**
     * Return the deferred callback.
     */
    public function promise(): mixed;

    /**
     * Decorate the message with the message decorator.
     */
    public function decorate(MessageDecorator $messageDecorator): void;

    /**
     * Check if an exception has been raised,
     * and if it is a stream not found exception.
     */
    public function hasStreamNotFound(): bool;

    /**
     * Check if an exception has been raised,
     * and if it is a stream already exists exception.
     */
    public function hasStreamAlreadyExits(): bool;

    /**
     * Check if an exception has been raised,
     * and if it is a concurrency exception.
     */
    public function hasConcurrency(): bool;
}
