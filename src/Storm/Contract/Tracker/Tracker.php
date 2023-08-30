<?php

declare(strict_types=1);

namespace Storm\Contract\Tracker;

use Illuminate\Support\Collection;

interface Tracker
{
    public function watch(string $eventName, callable $story, int $priority = 1): EventListener;

    public function disclose(Story $story): void;

    /**
     * fixMe invalid return type
     *
     * @template TReturn in bool|void
     *
     * @param callable(MessageStory|StreamStory|Story): (TReturn) $callback
     */
    public function discloseUntil(Story $story, callable $callback): void;

    public function forget(EventListener $eventListener): void;

    /**
     * @return Collection<EventListener> a clone instance of listeners
     */
    public function listeners(): Collection;
}
