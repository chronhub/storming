<?php

declare(strict_types=1);

namespace Storm\Projector\Support\ReadModel;

use Closure;

/**
 * @mixin InMemoryReadModel
 */
class MixinInMemoryStack
{
    public function insert(): Closure
    {
        return function (string $id, array $data): void {
            $this->stack('insert', $id, $data);
        };
    }

    public function update(): Closure
    {
        return function (string $id, string $key, mixed $value): void {
            $this->stack('update', $id, $key, $value);
        };
    }

    public function increment(): Closure
    {
        return function (string $id, string $key, int|float $value): void {
            $this->stack('increment', $id, $key, $value);
        };
    }

    public function decrement(): Closure
    {
        return function (string $id, string $key, int|float $value): void {
            $this->stack('decrement', $id, $key, $value);
        };
    }

    public function delete(): Closure
    {
        return function (string $id): void {
            $this->stack('delete', $id);
        };
    }
}
