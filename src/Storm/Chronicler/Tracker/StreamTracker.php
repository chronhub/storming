<?php

declare(strict_types=1);

namespace Storm\Chronicler\Tracker;

use Illuminate\Support\Collection;

interface StreamTracker
{
    /**
     * Add a listener to the stream tracker.
     */
    public function subscribe(string $eventName, callable $callback, int $priority = 0): Listener;

    /**
     * Add a listener to the stream tracker that will only be invoked once.
     */
    public function subscribeOnce(string $eventName, callable $callback, int $priority = 0): ListenerOnce;

    /**
     * Dispatch an event to all registered listeners sorted by descending priority.
     */
    public function disclose(string $eventName, mixed ...$arguments): mixed;

    /**
     * Remove a listener from the stream tracker.
     */
    public function forget(Listener $listener): void;

    /**
     * Get a clone of the listener collection.
     */
    public function listeners(): Collection;
}
