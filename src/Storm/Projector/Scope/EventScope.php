<?php

declare(strict_types=1);

namespace Storm\Projector\Scope;

use Closure;
use Illuminate\Support\Arr;
use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Projector\ProjectorScope;

use function array_walk;
use function in_array;

final class EventScope
{
    private bool $isAcked = false;

    public function __construct(
        private readonly DomainEvent $event,
        public readonly ProjectorScope $projector,
        public readonly ?UserStateScope $userState = null
    ) {
    }

    /**
     * @param class-string $event
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
     * @param class-string ...$events
     */
    public function ackOneOf(string ...$events): ?self
    {
        if (in_array($this->event::class, $events, true)) {
            return $this->ack($this->event::class);
        }

        return null;
    }

    /**
     * @param class-string $event
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
     * @template TEvent of DomainEvent
     * @template TProjector of ProjectorScope
     * @template TUserState of UserStateScope|null
     * @template TReturn of mixed
     *
     * @param  Closure(TEvent, TProjector, TUserState): TReturn $callback
     * @return static|TReturn
     */
    public function then(Closure $callback): mixed
    {
        if (! $this->isAcked) {
            return $this;
        }

        return $callback($this->event, $this->projector, $this->userState);
    }

    public function when(bool $condition, null|callable|array $callback = null, null|callable|array $fallback = null): ?self
    {
        if (blank($callback) && blank($fallback)) {
            return $condition ? $this : null;
        }

        $callbacks = Arr::wrap($condition && $callback !== null ? $callback : $fallback);

        array_walk($callbacks, fn (callable $callback) => $callback($this));

        return $this;
    }
}
