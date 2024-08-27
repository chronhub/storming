<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow;

/**
 * @template TListener of class-string|callable-string
 * @template THandler of class-string|callable|array{callable-string|callable}
 */
interface Notifier
{
    /**
     * Add Event listener with callback.
     *
     * @param TListener $event
     * @param THandler  $callback
     */
    public function listenTo(string $event, string|callable|array $callback): void;

    /**
     * Emit event and forget.
     *
     * @param TListener|object $event
     */
    public function notify(string|object $event, mixed ...$arguments): void;

    /**
     * Forget the listener and all its callbacks.
     * It also removes the listener from the list of emitted once listeners.
     *
     * @param TListener $listener
     */
    public function forgetListener(string $listener): void;

    /**
     * Reset the list of emitted once events.
     */
    public function resetOnce(): void;

    /**
     * Check if listener exists.
     *
     * @param TListener $listener
     */
    public function has(string $listener): bool;

    /**
     * Check if the event was emitted once.
     */
    public function wasEmittedOnce(string $event): bool;
}
