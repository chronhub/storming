<?php

declare(strict_types=1);

namespace Storm\Projector\Support\ReadModel;

use Illuminate\Support\Collection;
use Storm\Contract\Projector\ReadModel;

use function abs;

final class InMemoryReadModel implements ReadModel
{
    use InteractWithStack;

    private bool $initialized = false;

    /** @var Collection<string, array> */
    private Collection $container;

    public function initialize(): void
    {
        $this->container = new Collection();

        $this->initialized = true;
    }

    public function isInitialized(): bool
    {
        return $this->initialized;
    }

    public function reset(): void
    {
        $this->container = new Collection();

        $this->initialized = false;
    }

    public function down(): void
    {
        $this->reset();
    }

    public function getContainer(): array
    {
        return $this->container->all();
    }

    protected function insert(string $id, array $data): void
    {
        $this->container->put($id, $data);
    }

    protected function update(string $id, string $field, mixed $value): void
    {
        $data = $this->container->get($id);

        $data[$field] = $value;

        $this->container->put($id, $data);
    }

    protected function increment(string $id, string $field, int|float $value): void
    {
        $this->adjust($id, $field, $value, true);
    }

    protected function decrement(string $id, string $field, int|float $value): void
    {
        $this->adjust($id, $field, $value, false);
    }

    protected function delete(string $id): void
    {
        $this->container->forget($id);
    }

    private function adjust(string $id, string $field, int|float $value, bool $increment): void
    {
        $data = $this->container->get($id);

        $increment ? $data[$field] += abs($value) : $data[$field] -= abs($value);

        $this->container->put($id, $data);
    }
}
