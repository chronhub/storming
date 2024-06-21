<?php

declare(strict_types=1);

namespace Scope;

use Illuminate\Support\Arr;

use function array_merge;
use function is_array;

class ScopeState
{
    public function __construct(protected ?array $state = null)
    {
    }

    public function increment(string $field = 'count', int $value = 1): static
    {
        $this->updateUserState($field, $value, true);

        return $this;
    }

    public function update(string $field = 'count', int|string $value = 1, bool $increment = false): static
    {
        $this->updateUserState($field, $value, $increment);

        return $this;
    }

    public function merge(string $field, mixed $value): static
    {
        $oldValue = data_get($this->state, $field);

        $withMerge = is_array($oldValue) ? array_merge($oldValue, Arr::wrap($value)) : $value;

        Arr::set($this->state, $field, $withMerge);

        return $this;
    }

    public function reset(): void
    {
        $this->state = null;
    }

    private function updateUserState(string $field, $value, bool $increment): void
    {
        $oldValue = data_get($this->state, $field);

        $withValue = $increment ? $oldValue + $value : $value;

        Arr::set($this->state, $field, $withValue);
    }
}
