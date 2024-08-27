<?php

declare(strict_types=1);

namespace Storm\Projector\Support\ReadModel;

use function abs;

trait InMemoryContainerStack
{
    protected array $stack = [];

    protected array $container = [];

    public function persist(): void
    {
        foreach ($this->stack as $operation) {
            $operation();
        }

        $this->stack = [];
    }

    public function insert(int|string $id, array $data): void
    {
        $this->stack[] = fn () => $this->container[$id] = $data;
    }

    public function update(int|string $id, string $key, mixed $value): void
    {
        $this->stack[] = function () use ($id, $key, $value) {
            $this->container[$id][$key] = $value;
        };
    }

    public function increment(int|string $id, string $key, int|float $value): void
    {
        $this->stack[] = function () use ($id, $key, $value) {
            $this->container[$id][$key] += abs($value);
        };
    }

    public function decrement(int|string $id, string $key, int|float $value): void
    {
        $this->stack[] = function () use ($id, $key, $value) {
            $this->container[$id][$key] -= abs($value);
        };
    }

    public function delete(int|string $id): void
    {
        $this->stack[] = function () use ($id) {
            unset($this->container[$id]);
        };
    }
}
