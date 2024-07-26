<?php

declare(strict_types=1);

namespace Storm\Contract\Tracker;

use Throwable;

interface Story
{
    /**
     * Set the event.
     */
    public function withEvent(string $event): void;

    /**
     * Return the current event.
     */
    public function currentEvent(): string;

    /**
     * Stop the story.
     */
    public function stop(bool $stopPropagation): void;

    /**
     * Check if the story is stopped.
     */
    public function isStopped(): bool;

    /**
     * Set the exception.
     */
    public function withRaisedException(Throwable $exception): void;

    /**
     * Return the exception.
     */
    public function exception(): ?Throwable;

    /**
     * Reset the exception.
     */
    public function resetException(): void;

    /**
     * Check if the story has an exception.
     */
    public function hasException(): bool;
}
