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
        private readonly ProjectorScope $scope,
        private readonly ?UserStateScope $userState = null
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

    // return TValue|null
    public function then(Closure $callback): mixed
    {
        if (! $this->isAcked) {
            return null;
        }

        return $callback($this->event, $this->scope, $this->userState);
    }

    /**
     * Return the user state scope if it was initialized
     * It allows altering the state even if the event was not acked
     */
    public function state(): ?UserStateScope
    {
        return $this->userState;
    }

    public function when(bool $condition, null|callable|array $callback = null, null|callable|array $fallback = null): ?self
    {
        if (! $this->assertValidWhenCallbacks($callback, $fallback)) {
            return $condition ? $this : null;
        }

        $callbacks = Arr::wrap($condition && $callback !== null ? $callback : $fallback);

        array_walk($callbacks, fn (callable $callback) => $callback($this));

        return $this;
    }

    private function assertValidWhenCallbacks(null|callable|array $callback, null|callable|array $fallback): bool
    {
        if ($callback === null && $fallback === null) {
            return false;
        }

        if ($callback === [] && $fallback === []) {
            return false;
        }

        return true;
    }
}
