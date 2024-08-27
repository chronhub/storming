<?php

declare(strict_types=1);

namespace Storm\Chronicler\Tracker;

use Closure;
use Illuminate\Support\Collection;

use function is_iterable;

final class Tracker implements StreamTracker
{
    private Collection $listeners;

    public function __construct()
    {
        $this->listeners = new Collection;
    }

    public function subscribe(string $eventName, callable $callback, int $priority = 0): StreamListener
    {
        $listener = new StreamListener($eventName, $callback, $priority);

        $this->listeners->push($listener);

        return $listener;
    }

    public function subscribeOnce(string $eventName, callable $callback, int $priority = 0): ListenerOnce
    {
        $listener = new StreamListenerOnce($eventName, $callback, $priority);

        $this->listeners->push($listener);

        return $listener;
    }

    public function disclose(string $eventName, mixed ...$arguments): mixed
    {
        return $this->fireEvent($eventName, ...$arguments);
    }

    public function forget(Listener $listener): void
    {
        $this->listeners = $this->listeners->reject(
            static fn (Listener $subscriber): bool => $listener === $subscriber
        );
    }

    public function listeners(): Collection
    {
        return clone $this->listeners;
    }

    /**
     * Dispatch event and handle message
     */
    private function fireEvent(string $eventName, mixed ...$arguments): mixed
    {
        return $this->listeners
            ->filter(static fn (Listener $listener): bool => $eventName === $listener->name())
            ->sortByDesc(static fn (Listener $listener): int => $listener->priority(), SORT_NUMERIC)
            ->reduce(function (mixed $args, Listener $listener) {
                if ($listener instanceof ListenerOnce) {
                    $this->forget($listener);
                }

                // Execute the listener callback
                $result = is_iterable($args)
                    ? $listener->callback()(...$args)
                    : $listener->callback()($args);

                // If the result is a Closure, execute it to get the result
                if ($result instanceof Closure) {
                    $result = is_iterable($args)
                        ? $result(...$args)
                        : $result($args);
                }

                // Ensure the result is passed to the next listener
                return $result ?? $args;
            }, $arguments);
    }
}
