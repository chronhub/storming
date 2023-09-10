<?php

declare(strict_types=1);

namespace Storm\Tracker;

use Illuminate\Support\Collection;
use Storm\Contract\Tracker\Listener;
use Storm\Contract\Tracker\Story;

trait InteractWithTracker
{
    private Collection $listeners;

    /**
     * @var ?callable
     */
    private $subscriberResolver = null;

    public function __construct()
    {
        $this->listeners = new Collection();
    }

    public function listen(Listener $listener): Listener
    {
        $this->listeners->push($listener);

        return $listener;
    }

    public function watch(string $eventName, callable $story, int $priority = self::DEFAULT_PRIORITY): Listener
    {
        return $this->listen(
            $this->newListener($eventName, $story, $priority)
        );
    }

    public function disclose(Story $story): void
    {
        $this->fireEvent($story, null);
    }

    public function discloseUntil(Story $story, callable $callback): void
    {
        $this->fireEvent($story, $callback);
    }

    public function forget(Listener $listener): void
    {
        $this->listeners = $this->listeners->reject(
            static fn (Listener $subscriber): bool => $listener === $subscriber
        );
    }

    public function newListener(string $eventName, callable $story, int $priority = self::DEFAULT_PRIORITY): Listener
    {
        return new GenericListener($eventName, $story, $priority);
    }

    public function listeners(): Collection
    {
        return clone $this->listeners;
    }

    private function fireEvent(Story $story, ?callable $callback): void
    {
        $this->listeners
            ->filter(fn (Listener $listener): bool => $story->currentEvent() === $listener->name())
            ->sortByDesc(fn (Listener $listener): int => $listener->priority(), SORT_NUMERIC)
            ->each(function (Listener $listener) use ($story, $callback): bool {
                $listener->story()($story);

                if ($story->isStopped()) {
                    return false;
                }

                return ! ($callback && true === $callback($story));
            });
    }
}
