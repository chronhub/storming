<?php

declare(strict_types=1);

namespace Storm\Contract\Tracker;

use Illuminate\Support\Collection;

interface Tracker
{
    public const int DEFAULT_PRIORITY = 1;

    /**
     * Listen to an event.
     */
    public function listen(Listener $listener): Listener;

    /**
     * Watch an event.
     */
    public function watch(string $eventName, callable $story, int $priority = self::DEFAULT_PRIORITY): Listener;

    /**
     * Disclose the event story.
     */
    public function disclose(Story $story): void;

    /**
     * Disclose the event story until the callback returns true.
     *
     * @template TStory in MessageStory|StreamStory|Story
     * @template TReturn in true|void
     *
     * @param callable(TStory): TReturn $callback
     */
    public function discloseUntil(Story $story, callable $callback): void;

    /**
     * Forget the event listener.
     */
    public function forget(Listener $eventListener): void;

    /**
     * Return a new Listener instance.
     */
    public function newListener(string $eventName, callable $story, int $priority = self::DEFAULT_PRIORITY): Listener;

    /**
     * Return all listeners.
     *
     * @return Collection<Listener> a clone instance of listeners
     */
    public function listeners(): Collection;
}
