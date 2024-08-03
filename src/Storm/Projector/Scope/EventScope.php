<?php

declare(strict_types=1);

namespace Storm\Projector\Scope;

use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Projector\ProjectorScope;

use function in_array;

/**
 * @template TEvent of DomainEvent
 */
final class EventScope
{
    private bool $isAcked = false;

    public function __construct(
        /** @var TEvent $event */
        private readonly DomainEvent $event,
        public readonly ProjectorScope $projector,
        public readonly ?UserStateScope $userState = null
    ) {}

    /**
     * @param class-string<TEvent> $event
     */
    public function ack(string $event): ?self
    {
        if ($this->isAcked) {
            return $event === $this->event::class ? $this : null;
        }

        if ($event !== $this->event::class) {
            return null;
        }

        $this->isAcked = true;

        return $this;
    }

    /**
     * @param class-string<TEvent> ...$events
     */
    public function ackOneOf(string ...$events): ?self
    {
        if (in_array($this->event::class, $events, true)) {
            return $this->ack($this->event::class);
        }

        return null;
    }

    /**
     * @param class-string<TEvent> $event
     */
    public function match(string $event): bool
    {
        return $event === $this->event::class;
    }

    public function isAcked(): bool
    {
        return $this->isAcked;
    }

    public function event(): ?DomainEvent
    {
        return $this->isAcked ? $this->event : null;
    }

    /**
     * @template TProjectorScope of ProjectorScope
     * @template TUserStateScope of UserStateScope|null
     * @template TReturn of mixed
     *
     * @param  callable(TEvent, TProjectorScope, TUserStateScope): TReturn $callback
     * @return null|TReturn
     */
    public function then(callable $callback): mixed
    {
        if (! $this->isAcked) {
            return null;
        }

        return $callback($this->event, $this->projector, $this->userState);
    }
}
