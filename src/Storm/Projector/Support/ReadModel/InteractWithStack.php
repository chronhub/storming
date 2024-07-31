<?php

declare(strict_types=1);

namespace Storm\Projector\Support\ReadModel;

trait InteractWithStack
{
    /** @var array<array{string, array}>|array */
    protected array $stack = [];

    public function stack(string $method, mixed ...$arguments): void
    {
        $this->stack[] = [$method, $arguments];
    }

    public function persist(): void
    {
        foreach ($this->stack as [$method, $arguments]) {
            $this->{$method}(...$arguments);
        }

        $this->stack = [];
    }
}
