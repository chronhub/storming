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

use function array_key_exists;
use function array_merge;
use function in_array;

final readonly class InMemoryProjectionProvider implements ProjectionProvider
{
    private const array FIELDS_REQUIRED_ON_UPDATE = ['status', 'state', 'checkpoint', 'locked_until'];

    /**
     * @var Collection<string, ProjectionModel>
     */
    private Collection $projections;

    public function __construct(private SystemClock $clock)
    {
        $this->projections = new Collection();
    }

    public function createProjection(string $projectionName, ProjectionData $data): void
    {
        if ($this->exists($projectionName)) {
            throw ProjectionAlreadyExists::withName($projectionName);
        }

        $this->projections->put(
            $projectionName,
            ProjectionFactory::create($projectionName, $data->toArray()['status'])
        );
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
        $byStreamNames = static fn (ProjectionModel $projection): bool => in_array(
            $projection->name(), $projectionNames, true
        );

        return $this->projections->filter($byStreamNames)->keys()->toArray();
    }

    public function exists(string $projectionName): bool
    {
        return $this->projections->has($projectionName);
    }

    private function canAcquireLock(ProjectionModel $projection): bool
    {
        if ($projection->lockedUntil() === null) {
            return true;
        }

        return $this->clock->isGreaterThanNow($projection->lockedUntil());
    }

    private function retrieveOrFail(string $projectionName): ProjectionModel
    {
        $projection = $this->retrieve($projectionName);

        if (! $projection instanceof ProjectionModel) {
            throw ProjectionNotFound::withName($projectionName);
        }

        return $projection;
    }

    private function applyChanges(ProjectionModel $projection, array $data): void
    {
        $this->assertHasChangesOnUpdate($data, $projection->name());

        $projection = ProjectionFactory::fromArray(
            array_merge($projection->jsonSerialize(), $data)
        );

        $this->projections->put($projection->name(), $projection);
    }

    private function assertHasChangesOnUpdate(array $data, string $projectionName): void
    {
        $found = false;

        foreach (self::FIELDS_REQUIRED_ON_UPDATE as $field) {
            if (! array_key_exists($field, $data)) {
                continue;
            }

            $found = true;
        }

        if ($found === false) {
            throw new InMemoryProjectionFailed("Provide at least one change to update projection $projectionName");
        }
    }
}
