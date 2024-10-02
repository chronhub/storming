<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow;

use Illuminate\Support\Arr;
use Storm\Contract\Projector\ContextReader;
use Storm\Projector\Exception\RuntimeException;
use Storm\Projector\Factory\Component\ComponentSubscriber;
use Storm\Projector\Workflow\Notification\ResetOnlyOnceEmittedEvent;

use function array_key_exists;
use function array_merge;
use function in_array;
use function is_a;
use function is_object;
use function is_string;

/**
 * @template TListener of class-string|callable-string
 * @template THandler of class-string|callable|array|array{callable-string|callable}
 */
final class Notify implements ComponentSubscriber, Notifier
{
    final public const string RESET_ONCE_EMITTED_LISTENER = ResetOnlyOnceEmittedEvent::class;

    /** @var array<TListener, THandler> */
    protected array $listeners = [];

    /** @var array|array<class-string> */
    protected array $onceListeners = [];

    protected ?object $target = null;

    /**
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
     * @param TListener|object $event
     */
    public function notify(string|object $event, mixed ...$arguments): void
    {
        $eventClass = is_object($event) ? $event::class : $event;

        if (! $this->has($eventClass)) {
            $this->listenTo($eventClass, []);
        }

        $this->shouldBeEmittedOnce($eventClass);

        $notification = is_object($event) ? $event : new $event(...$arguments);

        $this->callHandlers($notification);
    }

    /**
     * @param TListener $listener
     */
    public function forgetListener(string $listener): void
    {
        unset($this->listeners[$listener]);
        unset($this->onceListeners[$listener]);
    }

    public function resetOnce(): void
    {
        $this->onceListeners = [];
    }

    public function has(string $listener): bool
    {
        return array_key_exists($listener, $this->listeners);
    }

    public function wasEmittedOnce(string $event): bool
    {
        return in_array($event, $this->onceListeners);
    }

    protected function callHandlers(object $listener): void
    {
        $callbacks = $this->listeners[$listener::class] ?? [];

        foreach ($callbacks as $callback) {
            if (is_string($callback)) {
                $callback = new $callback;
            }

            $callback($this->target, $listener);
        }
    }

    /**
     * Conditionally push the listener to the list of emitted events once.
     *
     * @param TListener $listener
     *
     * @throws RuntimeException when the listener marked as once has already been emitted
     */
    protected function shouldBeEmittedOnce(string $listener): void
    {
        if (is_a($listener, NotifyOnce::class, true)) {
            if (in_array($listener, $this->onceListeners)) {
                throw new RuntimeException("Listener $listener marked as once has already been emitted");
            }

            $listener === self::RESET_ONCE_EMITTED_LISTENER
                ? $this->resetOnce()
                : $this->onceListeners[] = $listener;
        }
    }

    public function subscribe(Process $process, ContextReader $context): void
    {
        $this->target = $process;
    }
}
