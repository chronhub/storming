<?php

declare(strict_types=1);

namespace Storm\Projector\Subscription;

use Closure;
use Illuminate\Support\Arr;
use Storm\Contract\Projector\AgentRegistry;
use Storm\Contract\Projector\EmitOnce;
use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Exception\RuntimeException;
use Storm\Projector\Workflow\Notification\ResetOnlyOnceEmittedEvent;

use function array_key_exists;
use function array_merge;
use function in_array;
use function is_a;
use function is_object;
use function is_string;

final class HubManager implements NotificationHub
{
    final public const string RESET_ONCE_EMITTED_EVENT = ResetOnlyOnceEmittedEvent::class;

    /** @var array|array<class-string, array<class-string|callable-string|callable>> */
    private array $events = [];

    /**
     * List of emitted once events.
     *
     * @var array|array<class-string>
     */
    private array $once = [];

    private ?AgentRegistry $agentRegistry = null;

    public function __construct() {}

    public function addEvent(string $event, string|callable|array $callback): void
    {
        $callbacks = Arr::wrap($callback);

        ! array_key_exists($event, $this->events)
            ? $this->events[$event] = $callbacks
            : $this->events[$event] = array_merge($this->events[$event], $callbacks);
    }

    public function addEvents(array $events): void
    {
        foreach ($events as $event => $handler) {
            $this->addEvent($event, $handler);
        }
    }

    public function forgetEvent(string $event): void
    {
        unset($this->events[$event]);

        unset($this->once[$event]);
    }

    public function hasEvent(string $event): bool
    {
        return array_key_exists($event, $this->events);
    }

    public function await(string|object $event, mixed ...$arguments): mixed
    {
        $this->shouldBeEmittedOnce($event);

        $notification = $this->makeEvent($event, ...$arguments);
        $result = $this->agentRegistry->capture($notification);

        if ($result === $notification) {
            $result = null;
        }

        $this->handleEvent($notification, $result);

        return $result;
    }

    public function emit(string|object $event, mixed ...$arguments): void
    {
        $eventClass = $this->eventClass($event);

        if (! $this->hasEvent($eventClass)) {
            $this->addEvent($eventClass, fn () => null);
        }

        $this->await($event, ...$arguments);
    }

    public function emitMany(string|object ...$events): void
    {
        foreach ($events as $event) {
            $this->emit($event);
        }
    }

    public function emitWhen(bool $condition, ?Closure $onSuccess = null, ?Closure $onFailure = null): self
    {
        value($condition ? $onSuccess : $onFailure, $this);

        return $this;
    }

    public function resetOnce(): void
    {
        $this->once = [];
    }

    /**
     * @internal
     */
    public function setAgentRegistry(AgentRegistry $agentRegistry): void
    {
        $this->agentRegistry = $agentRegistry;
    }

    /**
     * Handle the event.
     */
    private function handleEvent(object $event, mixed $result): void
    {
        $callbacks = $this->events[$event::class] ?? [];

        foreach ($callbacks as $callback) {
            if (is_string($callback)) {
                $callback = new $callback();
            }

            $callback($this, $event, $result);
        }
    }

    /**
     * Return the event object.
     *
     * @param class-string|object $event
     */
    private function makeEvent(string|object $event, mixed ...$arguments): object
    {
        if (is_object($event)) {
            return $event;
        }

        return new $event(...$arguments);
    }

    /**
     * Conditionally push the event to the list of emitted events once.
     *
     * @param class-string|object $event
     *
     * @throws RuntimeException when the event marked as once has already been emitted
     */
    private function shouldBeEmittedOnce(string|object $event): void
    {
        if (is_a($event, EmitOnce::class, true)) {
            $eventClassName = $this->eventClass($event);

            if (in_array($eventClassName, $this->once)) {
                throw new RuntimeException("Event $eventClassName marked as once has already been emitted");
            }

            $eventClassName === self::RESET_ONCE_EMITTED_EVENT
                ? $this->resetOnce()
                : $this->once[] = $eventClassName;
        }
    }

    /**
     * Return the event class name.
     *
     * @param  class-string|object $event
     * @return class-string
     */
    private function eventClass(string|object $event): string
    {
        return is_object($event) ? $event::class : $event;
    }
}
