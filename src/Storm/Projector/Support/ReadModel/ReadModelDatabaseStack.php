<?php

declare(strict_types=1);

namespace Storm\Projector\Support\ReadModel;

use Illuminate\Database\Query\Builder;
use Storm\Contract\Projector\ReadModel;

/**
 * @phpstan-require-implements ReadModel
 *
 * @method static Builder query()
 */
trait ReadModelDatabaseStack
{
    /** @var array<(Closure(): void)> */
    protected array $stack = [];

    public function insert(array $data): void
    {
        $this->stack[] = fn () => $this->query()->insert($data);
    }

    public function update(string $id, array $data): void
    {
        $this->stack[] = fn () => $this->query()->where($this->getKey(), $id)->update($data);
    }

    public function increment(string $id, string $column, int $amount, array $extra = []): void
    {
        $this->stack[] = fn () => $this->query()
            ->where($this->getKey(), $id)
            ->increment($column, $amount, $extra);
    }

    public function incrementEach(string $id, array $columns, array $extra = []): void
    {
        $this->stack[] = fn () => $this->query()
            ->where($this->getKey(), $id)
            ->incrementEach($columns, $extra);
    }

    public function decrement(string $id, string $column, int $amount, array $extra = []): void
    {
        $this->stack[] = fn () => $this->query()
            ->where($this->getKey(), $id)
            ->decrement($column, $amount, $extra);
    }

    public function decrementEach(string $id, array $columns, array $extra = []): void
    {
        $this->stack[] = fn () => $this->query()
            ->where($this->getKey(), $id)
            ->decrementEach($columns, $extra);
    }

    public function delete(string $id): void
    {
        $this->stack[] = fn () => $this->query()->where($this->getKey(), $id)->delete();
    }

    public function persist(): void
    {
        foreach ($this->stack as $operation) {
            $operation();
        }

        $this->stack = [];
    }

    /**
     * Define the primary key for the model.
     */
    abstract protected function getKey(): string;
}
