<?php

declare(strict_types=1);

namespace Storm\Projector\Scope;

use ArrayAccess;
use Illuminate\Support\Arr;

use function array_key_exists;
use function array_merge;
use function is_array;

// todo: see laravel fluent or attributes
class UserStateScope implements ArrayAccess
{
    public function __construct(
        public array $state
    ) {}

    /**
     * Update the value of an existing field or insert a new field with the given value.
     *
     * @return $this
     */
    public function upsert(string $field, mixed $value): self
    {
        $this->updateUserState($field, $value, false);

        return $this;
    }

    /**
     * Increment the value of an existing field or insert a new field with the given value.
     *
     * @return $this
     */
    public function increment(string $field = 'count', int $value = 1): self
    {
        $this->updateUserState($field, $value, true);

        return $this;
    }

    /**
     * Decrement the value of an existing field or insert a new field with the given value.
     *
     * @return $this
     */
    public function decrement(string $field = 'count', int $value = -1): self
    {
        $this->updateUserState($field, $value > 0 ? -$value : $value, true);

        return $this;
    }

    /**
     * Merge the value of an existing field or insert a new field with the given value.
     *
     * @return $this
     */
    public function merge(string $field, mixed $value): self
    {
        //fixMe if value is not array, it updates the field with the value
        //force value to be an array
        $oldValue = data_get($this->state, $field);

        $withMerge = is_array($oldValue) ? array_merge($oldValue, Arr::wrap($value)) : $value;

        Arr::set($this->state, $field, $withMerge);

        return $this;
    }

    /**
     * Forget the value of an existing field.
     *
     * @return $this
     */
    public function forget(string $field): self
    {
        Arr::forget($this->state, $field);

        return $this;
    }

    /**
     * Return the current state.
     *
     * @deprecated use $this->state instead
     */
    public function state(): array
    {
        return $this->state;
    }

    /**
     * Check if the state has a field.
     */
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

    /**
     * @return mixed default to null
     */
    public function offsetGet(mixed $offset): mixed
    {
        return data_get($this->state, $offset);
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
