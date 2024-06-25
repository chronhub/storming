<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

use Closure;

/**
 * @template TListener of class-string|callable-string
 * @template TLHandler of class-string|callable|array{callable-string|callable}
 */
interface NotificationHub
{
    /**
     * Add hook.
     *
     * @param class-string $hook
     */
    public function addHook(string $hook, callable $trigger): void;

    /**
     * Add hooks.
     *
     * @param array<class-string, callable> $hooks
     */
    public function addHooks(array $hooks): void;

    /**
     * Trigger hook.
     */
    public function trigger(object $hook): void;

    /**
     * Add listener.
     *
     * @param TListener $listener
     * @param TLHandler $callback
     */
    public function addListener(string $listener, string|callable|array $callback): void;

    /**
     * Add listeners
     *
     * @param array<TListener, TLHandler> $listeners
     */
    public function addListeners(array $listeners): void;

    /**
     * Forget the event and all its callbacks.
     *
     * @param TListener $listener
     */
    public function forgetListener(string $listener): void;

    /**
     * Forget all listeners.
     */
    public function forgetAll(): void;

    /**
     * Fire event and forget.
     *
     * @param TListener|object $listener
     */
    public function notify(string|object $listener, mixed ...$arguments): void;

    /**
     * Fire many listeners and forget.
     *
     * @param TListener|object ...$listeners
     */
    public function notifyMany(string|object ...$listeners): void;

    /**
     * Fire event with condition.
     *
     * @param Closure(self): void      $onSuccess
     * @param null|Closure(self): void $onFailure
     */
    public function notifyWhen(bool $condition, Closure $onSuccess, ?Closure $onFailure = null): self;

    /**
     * Fire event and call handler.
     */
    public function expect(string|object $event, mixed ...$arguments): mixed;
}
