<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

use Closure;

/**
 * @template TListener of class-string|callable-string
 * @template THandler of class-string|callable|array{callable-string|callable}
 */
interface NotificationHub
{
    /**
     * Add Event listener with callback.
     *
     * @param TListener $event
     * @param THandler  $callback
     */
    public function addEvent(string $event, string|callable|array $callback): void;

    /**
     * Add Event listeners with callbacks.
     *
     * @param array<TListener, THandler> $events
     */
    public function addEvents(array $events): void;

    /**
     * Emit event and wait with expectation.
     */
    public function await(string|object $expectation, mixed ...$arguments): mixed;

    /**
     * Emit event and forget.
     *
     * @param TListener|object $event
     */
    public function emit(string|object $event, mixed ...$arguments): void;

    /**
     * Forget the event and all its callbacks.
     * It also removes the event from the list of emitted once events.
     *
     * @param TListener $event
     */
    public function forgetEvent(string $event): void;

    /**
     * Emit many events and forget.
     *
     * @param TListener|object ...$events
     */
    public function emitMany(string|object ...$events): void;

    /**
     * Emit event with condition.
     *
     * @param Closure(self): void      $onSuccess
     * @param null|Closure(self): void $onFailure
     */
    public function emitWhen(bool $condition, Closure $onSuccess, ?Closure $onFailure = null): self;

    /**
     * Check if event exists.
     *
     * @param TListener $event
     */
    public function hasEvent(string $event): bool;

    /**
     * Reset the list of emitted once events.
     */
    public function resetOnce(): void;
}
