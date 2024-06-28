<?php

declare(strict_types=1);

namespace Storm\Projector\Scope;

use ArrayAccess;
use Illuminate\Support\Arr;

use function array_key_exists;
use function array_merge;
use function is_array;

class UserStateScope implements ArrayAccess
{
    public function __construct(
        protected array $state
    ) {
    }

    public function upsert(string $field, mixed $value): self
    {
        $this->updateUserState($field, $value, false);

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
        //fixMe if value is not array, it updates the field with the value
        //force value to be an array
        $oldValue = data_get($this->state, $field);

        $withMerge = is_array($oldValue) ? array_merge($oldValue, Arr::wrap($value)) : $value;

        Arr::set($this->state, $field, $withMerge);

        return $this;
    }

    public function forget(string $field): self
    {
        Arr::forget($this->state, $field);

        return $this;
    }

    public function state(): array
    {
        return $this->state;
    }

    public function has(string $field): bool
    {
        return array_key_exists($field, $this->state);
    }

    private function updateUserState(string $field, mixed $value, bool $increment): void
    {
        $oldValue = data_get($this->state, $field);

        $withValue = $increment ? $oldValue + $value : $value;

        Arr::set($this->state, $field, $withValue);
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->has($offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->state[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->upsert($offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        $this->forget($offset);
    }
}
