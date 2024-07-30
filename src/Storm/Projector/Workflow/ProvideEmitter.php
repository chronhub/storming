<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow;

use Illuminate\Support\Arr;
use Storm\Contract\Projector\EmitOnce;
use Storm\Projector\Exception\RuntimeException;
use Storm\Projector\Workflow\Notification\ResetOnlyOnceEmittedEvent;

use function array_key_exists;
use function array_merge;
use function in_array;
use function is_a;
use function is_callable;
use function is_object;
use function is_string;

/**
 * @template TListener of class-string|callable-string
 * @template THandler of class-string|callable|array{callable-string|callable}
 */
trait ProvideEmitter
{
    final public const string RESET_ONCE_EMITTED_LISTENER = ResetOnlyOnceEmittedEvent::class;

    /** @var array|array<class-string, array<class-string|callable-string|callable>> */
    protected array $listeners = [];

    /**
     * List of emitted once events.
     *
     * @var array|array<class-string>
     */
    protected array $once = [];

    /**
     * Add Event listener with callback.
     *
     * @param TListener $event
     * @param THandler  $callback
     */
    public function listenTo(string $event, string|callable|array $callback): void
    {
        $callbacks = Arr::wrap($callback);

        ! array_key_exists($event, $this->listeners)
            ? $this->listeners[$event] = $callbacks
            : $this->listeners[$event] = array_merge($this->listeners[$event], $callbacks);
    }

    /**
     * Emit event and forget.
     *
     * @param TListener|object $event
     */
    public function emit(string|object $event, mixed ...$arguments): void
    {
        $eventClass = $this->listenerClass($event);

        if (! $this->has($eventClass)) {
            $this->listenTo($eventClass, fn () => null);
        }

        $this->shouldBeEmittedOnce($event);

        $notification = is_object($event) ? $event : new $event(...$arguments);

        if (is_callable($notification)) {
            $notification($this);
        }

        $this->callHandlers($notification);
    }

    /**
     * Forget the listener and all its callbacks.
     * It also removes the listener from the list of emitted once listeners.
     *
     * @param TListener|object $listener
     */
    public function forgetListener(string $listener): void
    {
        unset($this->listeners[$listener]);
        unset($this->once[$listener]);
    }

    public function resetOnce(): void
    {
        $this->once = [];
    }

    /**
     * Check if listener exists.
     *
     * @param TListener $listener
     */
    public function has(string $listener): bool
    {
        return array_key_exists($listener, $this->listeners);
    }

    public function wasEmittedOnce(string $event): bool
    {
        return in_array($event, $this->once);
    }

    /**
     * Handle the listener.
     */
    protected function callHandlers(object $listener): void
    {
        $callbacks = $this->listeners[$listener::class] ?? [];

        foreach ($callbacks as $callback) {
            if (is_string($callback)) {
                $callback = new $callback();
            }

            $callback($this, $listener);
        }
    }

    /**
     * Conditionally push the listener to the list of emitted events once.
     *
     * @param class-string|object $listener
     *
     * @throws RuntimeException when the listener marked as once has already been emitted
     */
    protected function shouldBeEmittedOnce(string|object $listener): void
    {
        if (is_a($listener, EmitOnce::class, true)) {
            $listenerClass = $this->listenerClass($listener);

            if (in_array($listenerClass, $this->once)) {
                throw new RuntimeException("Listener $listenerClass marked as once has already been emitted");
            }

            $listenerClass === self::RESET_ONCE_EMITTED_LISTENER
                ? $this->resetOnce()
                : $this->once[] = $listenerClass;
        }
    }

    /**
     * Return the listener class name.
     *
     * @param  class-string|object $listener
     * @return class-string
     */
    protected function listenerClass(string|object $listener): string
    {
        return is_object($listener) ? $listener::class : $listener;
    }
}
