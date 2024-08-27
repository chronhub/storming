<?php

declare(strict_types=1);

namespace Storm\Story;

use Storm\Message\Message;

interface StoryContext
{
    /**
     * Handle the given message.
     */
    public function __invoke(array|object $payload, ?object $job = null): mixed;

    /**
     * Build a message from the given payload.
     */
    public function buildMessage(array|object $payload): Message;

    /**
     * Build a job for the given message and queue.
     */
    public function buildJob(Message $message, ?array $queue = null): object;
}
