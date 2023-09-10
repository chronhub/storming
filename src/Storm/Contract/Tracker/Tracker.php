<?php

declare(strict_types=1);

namespace Storm\Contract\Tracker;

use Illuminate\Support\Collection;

interface Tracker
{
    public const DEFAULT_PRIORITY = 1;

    public function listen(Listener $listener): Listener;

    public function watch(string $eventName, callable $story, int $priority = self::DEFAULT_PRIORITY): Listener;

    public function disclose(Story $story): void;

    /**
     * fixMe invalid callback return type
     *
     * @template TReturn in bool|void
     *
     * @param callable(MessageStory|StreamStory|Story): (TReturn) $callback
     */
    public function discloseUntil(Story $story, callable $callback): void;

    public function forget(Listener $eventListener): void;

    public function newListener(string $eventName, callable $story, int $priority = self::DEFAULT_PRIORITY): Listener;

    /**
     * @return Collection<Listener> a clone instance of listeners
     */
    public function listeners(): Collection;
}
