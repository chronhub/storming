<?php

declare(strict_types=1);

namespace Storm\Projector\Repository;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Projector\ProjectionModel;
use Storm\Contract\Projector\ProjectionProvider;
use Storm\Projector\Exception\InvalidArgumentException;
use Storm\Projector\Exception\ProjectionAlreadyExists;
use Storm\Projector\Exception\ProjectionAlreadyRunning;
use Storm\Projector\Exception\ProjectionConnectionFailed;
use Storm\Projector\Exception\ProjectionNotFound;
use Storm\Projector\Repository\Data\CreateData;
use Storm\Projector\Repository\Data\ProjectionData;
use Storm\Projector\Repository\Data\StartData;

use function sprintf;

final readonly class DatabaseProjectionProvider implements ProjectionProvider
{
    private const array FAILS = [
        'create' => 'Fail to create projection with name %s',
        'update' => 'Failed to update projection with name %s and data class %s',
        'delete' => 'Failed to delete projection with name %s',
    ];

    final public const string TABLE_NAME = 'projections';

    public function __construct(
        private Connection $connection,
        private SystemClock $clock,
        private ?string $projectionTableName = self::TABLE_NAME,
    ) {}

    public function createProjection(string $projectionName, ProjectionData $data): void
    {
        if (! $data instanceof CreateData) {
            throw new InvalidArgumentException(sprintf('Invalid data provided, expected class %s', CreateData::class));
        }

        if ($this->exists($projectionName)) {
            throw ProjectionAlreadyExists::withName($projectionName);
        }

        $projection = new Projection($projectionName, $data->status, null, null, null);

        $success = $this->query()->insert($projection->jsonSerialize());

        if (! $success) {
            $this->raiseOperationFailed('create', $projectionName);
        }
    }

    public function acquireLock(string $projectionName, ProjectionData $data): void
    {
        if (! $data instanceof StartData) {
            throw new InvalidArgumentException(sprintf('Invalid data provided, expected class %s', StartData::class));
        }

        $success = $this->query()
            ->where('name', $projectionName)
            ->where(function (Builder $query): void {
                $query->whereRaw('locked_until IS NULL OR locked_until < ?', [$this->clock->generate()]);
            })
            ->update([
                'status' => $data->status,
                'locked_until' => $data->lockedUntil,
            ]);

        if ($success === 0) {
            $this->assertProjectionExists($projectionName);

            throw ProjectionAlreadyRunning::withName($projectionName);
        }
    }

    public function updateProjection(string $projectionName, ProjectionData $data): void
    {
        $success = $this->query()->where('name', $projectionName)->update($data->toArray());

        if ($success === 0) {
            $this->assertProjectionExists($projectionName);

            $this->raiseOperationFailed('update', $projectionName, $data::class);
        }
    }

    public function deleteProjection(string $projectionName): void
    {
        $success = $this->query()->where('name', $projectionName)->delete();

        if ($success === 0) {
            $this->assertProjectionExists($projectionName);

            $this->raiseOperationFailed('delete', $projectionName);
        }
    }

    public function retrieve(string $projectionName): ?ProjectionModel
    {
        $projection = $this->query()->where('name', $projectionName)->first();

        if ($projection === null) {
            return null;
        }

        return ProjectionFactory::make($projection);
    }

    public function filterByNames(string ...$projectionNames): array
    {
        return $this->query()->whereIn('name', $projectionNames)->pluck('name')->all();
    }

    public function exists(string $projectionName): bool
    {
        return $this->query()->where('name', $projectionName)->exists();
    }

    private function assertProjectionExists(string $projectionName): void
    {
        if (! $this->exists($projectionName)) {
            throw ProjectionNotFound::withName($projectionName);
        }
    }

    private function raiseOperationFailed(string $operation, string $projectionName, ?string $dataClass = null): void
    {
        $message = sprintf(self::FAILS[$operation], $projectionName, $dataClass);

        throw new ProjectionConnectionFailed($message);
    }

    private function query(): Builder
    {
        return $this->connection->table($this->projectionTableName);
    }
}
