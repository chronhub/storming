<?php

declare(strict_types=1);

namespace Storm\Projector\Support\ReadModel;

use Illuminate\Support\Traits\Macroable;
use Storm\Contract\Projector\ReadModel;

use function abs;

/**
 * @template T
 *
 * @mixin T
 */
final class InMemoryReadModel implements ReadModel
{
    use Macroable;

    protected array $container = [];

    protected array $stack = [];

    public function __construct()
    {
        self::mixin(new MixinInMemoryStack(), false);
    }

    public function initialize(): void
    {
        $this->container = [];
    }

    public function isInitialized(): bool
    {
        return true;
    }

    public function persist(): void
    {
        foreach ($this->stack as [$method, $arguments]) {
            $this->{$method}(...$arguments);
        }

        $this->stack = [];
    }

    public function reset(): void
    {
        $this->container = [];
    }

    public function down(): void
    {
        $this->reset();
    }

    public function getContainer(): array
    {
        return $this->container;
    }

    /**
     * Stack all operations.
     */
    protected function stack(string $method, mixed ...$arguments): void
    {
        $this->stack[] = [$method, $arguments];
    }

    protected function insert(string $id, array $data): void
    {
        $this->container[$id] = $data;
    }

    protected function update(string $id, string $field, mixed $value): void
    {
        $this->container[$id][$field] = $value;
    }

    protected function increment(string $id, string $field, int|float $value): void
    {
        $this->container[$id][$field] += abs($value);
    }

    protected function decrement(string $id, string $field, int|float $value): void
    {
        $this->container[$id][$field] -= abs($value);
    }

    protected function delete(string $id): void
    {
        unset($this->container[$id]);
    }
}
