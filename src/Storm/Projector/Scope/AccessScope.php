<?php

declare(strict_types=1);

namespace Storm\Projector\Scope;

use Illuminate\Support\Arr;
use Storm\Contract\Message\DomainEvent;

use function array_key_exists;
use function array_merge;
use function in_array;
use function is_array;
use function is_int;

class AccessScope
{
    protected bool $isAcked = false;

    public function __construct(
        protected DomainEvent $event,
        protected ?array $state = null
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

    public function upsert(string $field = 'count', null|int|string|array|bool $value = 1, bool $increment = false): self
    {
        if ($increment && ! is_int($value)) {
            return $this;
        }

        $this->updateUserState($field, $value, $increment);

        return $this;
    }

    public function increment(string $field = 'count', int $value = 1): self
    {
        if (! $this->has($field)) {
            return $this;
        }

        $this->updateUserState($field, $value, true);

        return $this;
    }

    public function decrement(string $field = 'count', int $value = -1): self
    {
        if (! $this->has($field)) {
            return $this;
        }

        $this->updateUserState($field, $value > 0 ? -$value : $value, true);

        return $this;
    }

    public function merge(string $field, mixed $value): self
    {
        $oldValue = data_get($this->state, $field);

        $withMerge = is_array($oldValue) ? array_merge($oldValue, Arr::wrap($value)) : $value;

        Arr::set($this->state, $field, $withMerge);

        return $this;
    }

    public function state(): ?array
    {
        return $this->state;
    }

    public function has(string $field): bool
    {
        return array_key_exists($field, $this->state);
    }

    private function updateUserState(string $field, $value, bool $increment): void
    {
        $oldValue = data_get($this->state, $field);

        $withValue = $increment ? $oldValue + $value : $value;

        Arr::set($this->state, $field, $withValue);
    }
}
