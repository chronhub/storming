<?php

declare(strict_types=1);

namespace Storm\Tracker;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Collection;
use Storm\Contract\Tracker\Listener;
use Storm\Contract\Tracker\Story;
use Storm\Support\Attribute\AttributeResolver;
use Storm\Support\Attribute\ResolveSubscriber;

trait InteractWithTracker
{
    private Collection $listeners;

    private ?Container $container = null;

    public function __construct()
    {
        $this->listeners = new Collection();
    }

    public function watch(object|string $subscriber): array
    {
        if (! $subscriber instanceof Listener) {
            $subscriber = (new ResolveSubscriber(new AttributeResolver()))
                ->resolve($subscriber);
        }

        return Collection::wrap($subscriber)->each(function (Listener $listener): void {
            $this->listeners->push($listener);
        })->toArray();
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

    public function listeners(): Collection
    {
        return clone $this->listeners;
    }

    /**
     * Dispatch event and handle message
     */
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
