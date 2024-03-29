<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

use Closure;

interface NotificationHub
{
    /**
     * Add hook
     *
     * @param class-string $hook
     */
    public function addHook(string $hook, callable $trigger): void;

    /**
     * Add hooks
     *
     * @param array<class-string, callable> $hooks
     */
    public function addHooks(array $hooks): void;

    /**
     * Trigger hook
     */
    public function trigger(object $hook): void;

    /**
     * Add listener
     */
    public function addListener(string $event, string|callable|array $callback): void;

    /**
     * Add listeners
     *
     * @param array<class-string, string|callable> $listeners
     */
    public function addListeners(array $listeners): void;

    /**
     * Forget the event and all its callbacks
     */
    public function forgetListener(string $event): void;

    /**
     * Forget all events and all its callbacks
     */
    public function forgetAll(): void;

    /**
     * Fire event and forget
     */
    public function notify(string|object $event, mixed ...$arguments): void;

    /**
     * Fire many events and forget
     *
     * @param class-string|object ...$events
     */
    public function notifyMany(string|object ...$events): void;

    /**
     * Fire event with condition and notify on success or fallback
     */
    public function notifyWhen(bool $condition, ?Closure $onSuccess = null, ?Closure $fallback = null): self;

    /**
     * Fire event and wait for response
     */
    public function expect(string|object $event, mixed ...$arguments): mixed;
}
