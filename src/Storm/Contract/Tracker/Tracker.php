<?php

declare(strict_types=1);

namespace Storm\Contract\Tracker;

use Illuminate\Support\Collection;

interface Tracker
{
    public const DEFAULT_PRIORITY = 1;

    /**
     * @return array<Listener>
     */
    public function watch(object|string $subscriber): array;

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

    /**
     * @return Collection<Listener> a clone instance of listeners
     */
    public function listeners(): Collection;
}
