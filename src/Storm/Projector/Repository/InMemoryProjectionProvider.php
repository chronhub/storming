<?php

declare(strict_types=1);

namespace Storm\Projector\Repository;

use Illuminate\Support\Collection;
use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Projector\ProjectionModel;
use Storm\Contract\Projector\ProjectionProvider;
use Storm\Projector\Exception\InMemoryProjectionFailed;
use Storm\Projector\Exception\ProjectionAlreadyExists;
use Storm\Projector\Exception\ProjectionAlreadyRunning;
use Storm\Projector\Exception\ProjectionNotFound;
use Storm\Projector\Repository\Data\ProjectionData;

use function array_flip;
use function array_key_exists;
use function array_merge;

final readonly class InMemoryProjectionProvider implements ProjectionProvider
{
    /** @var array{'name', 'status', 'state', 'checkpoint', 'locked_until'} */
    private array $attributes;

    /** @var Collection<string, ProjectionModel> */
    private Collection $projections;

    public function __construct(private SystemClock $clock)
    {
        $this->projections = new Collection;
        $this->attributes = ['name', 'status', 'state', 'checkpoint', 'locked_until'];
    }

    public function createProjection(string $projectionName, ProjectionData $data): void
    {
        if ($this->exists($projectionName)) {
            throw ProjectionAlreadyExists::withName($projectionName);
        }

        $projection = ProjectionFactory::create($projectionName, $data->toArray()['status']);

        $this->projections->put($projectionName, $projection);
    }

    public function acquireLock(string $projectionName, ProjectionData $data): void
    {
        $projection = $this->retrieveOrFail($projectionName);

        if (! $this->canAcquireLock($projection)) {
            throw ProjectionAlreadyRunning::withName($projectionName);
        }

        $this->applyChanges($projection, $data->toArray());
    }

    public function updateProjection(string $projectionName, ProjectionData $data): void
    {
        $projection = $this->retrieveOrFail($projectionName);

        $this->applyChanges($projection, $data->toArray());
    }

    public function deleteProjection(string $projectionName): void
    {
        $this->retrieveOrFail($projectionName);

        $this->projections->forget($projectionName);
    }

    public function retrieve(string $projectionName): ?ProjectionModel
    {
        return $this->projections->get($projectionName);
    }

    public function filterByNames(string ...$projectionNames): array
    {
        return $this->projections
            ->keys()
            ->only(array_flip($projectionNames))
            ->toArray();
    }

    public function exists(string $projectionName): bool
    {
        return $this->projections->has($projectionName);
    }

    /**
     * Check if the projection can acquire a lock.
     */
    private function canAcquireLock(ProjectionModel $projection): bool
    {
        if ($projection->lockedUntil() === null) {
            return true;
        }

        return $this->clock->now()->isGreaterThan($projection->lockedUntil());
    }

    /**
     * Retrieve a projection or fail.
     *
     * @throws ProjectionNotFound
     */
    private function retrieveOrFail(string $projectionName): ProjectionModel
    {
        $projection = $this->retrieve($projectionName);

        if (! $projection instanceof ProjectionModel) {
            throw ProjectionNotFound::withName($projectionName);
        }

        return $projection;
    }

    /**
     * Apply changes to a projection.
     *
     * @throws InMemoryProjectionFailed
     */
    private function applyChanges(ProjectionModel $projection, array $data): void
    {
        $this->assertHasChangesOnUpdate($data, $projection->name());

        $projection = ProjectionFactory::fromArray(
            array_merge($projection->jsonSerialize(), $data)
        );

        $this->projections->put($projection->name(), $projection);
    }

    /**
     * Assert that the projection has changes on update.
     *
     * @throws InMemoryProjectionFailed when no changes are provided
     */
    private function assertHasChangesOnUpdate(array $data, string $projectionName): void
    {
        foreach ($this->attributes as $attribute) {
            if (array_key_exists($attribute, $data)) {
                return;
            }
        }

        throw new InMemoryProjectionFailed("Provide at least one change to update projection $projectionName");
    }
}
