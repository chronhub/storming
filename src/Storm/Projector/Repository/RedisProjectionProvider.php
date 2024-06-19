<?php

declare(strict_types=1);

namespace Storm\Projector\Repository;

use Illuminate\Cache\RedisStore;
use Illuminate\Contracts\Cache\Repository;
use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Projector\ProjectionProvider;
use Storm\Projector\Exception\InvalidArgumentException;
use Storm\Projector\Exception\ProjectionAlreadyExists;
use Storm\Projector\Exception\ProjectionAlreadyRunning;
use Storm\Projector\Exception\ProjectionNotFound;
use Storm\Projector\Repository\Data\CreateData;
use Storm\Projector\Repository\Data\ProjectionData;
use Storm\Projector\Repository\Data\StartData;

use function array_filter;
use function array_merge;
use function is_string;
use function json_decode;
use function json_encode;
use function sprintf;

//wip
final readonly class RedisProjectionProvider implements ProjectionProvider
{
    public const string PROJECTION_PREFIX_KEY = 'storm:projections';

    private RedisStore $store;

    public function __construct(
        Repository $repository,
        private SystemClock $clock,
    ) {
        $store = $repository->getStore();

        if (! $store instanceof RedisStore) {
            throw new InvalidArgumentException('Invalid cache store provided, expected RedisStore');
        }

        $this->store = $store;
        $this->store->setPrefix(self::PROJECTION_PREFIX_KEY);
    }

    public function createProjection(string $projectionName, ProjectionData $data): void
    {
        /** @var CreateData $data */
        $this->assertDataInstance($data, CreateData::class);

        if ($this->exists($projectionName)) {
            throw ProjectionAlreadyExists::withName($projectionName);
        }

        $projection = ProjectionFactory::create($projectionName, $data->status);

        $this->store->forever($this->determineKey($projectionName), $projection->jsonSerialize());
    }

    public function acquireLock(string $projectionName, ProjectionData $data): void
    {
        /** @var StartData $data */
        $this->assertDataInstance($data, StartData::class);

        $projection = $this->retrieveOrFail($projectionName);

        if (! $this->canAcquireLock($projection)) {
            throw ProjectionAlreadyRunning::withName($projectionName);
        }

        $updatedProjection = new Projection(
            $projectionName,
            $data->status,
            $projection->state(),
            $projection->checkpoint(),
            $data->lockedUntil
        );

        $this->store->forever(
            $this->determineKey($projectionName),
            json_encode($updatedProjection->jsonSerialize())
        );
    }

    public function updateProjection(string $projectionName, ProjectionData $data): void
    {
        $projection = $this->retrieveOrFail($projectionName);

        $this->store->forever(
            $this->determineKey($projectionName),
            json_encode(array_merge($projection->jsonSerialize(), $data->toArray()))
        );
    }

    public function deleteProjection(string $projectionName): void
    {
        if (! $this->exists($projectionName)) {
            throw ProjectionNotFound::withName($projectionName);
        }

        $this->store->forget($this->determineKey($projectionName));
    }

    public function retrieve(string $projectionName): ?Projection
    {
        $data = $this->store->get($this->determineKey($projectionName));

        if ($data === null) {
            return null;
        }

        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        return ProjectionFactory::make($data);
    }

    public function exists(string $projectionName): bool
    {
        return $this->retrieve($projectionName) !== null;
    }

    public function filterByNames(string ...$projectionNames): array
    {
        return array_filter($projectionNames, fn (string $projectionName) => $this->exists($projectionName));
    }

    private function retrieveOrFail(string $projectionName): Projection
    {
        $projection = $this->retrieve($projectionName);

        if ($projection === null) {
            throw ProjectionNotFound::withName($projectionName);
        }

        return $projection;
    }

    private function canAcquireLock(Projection $projection): bool
    {
        if ($projection->lockedUntil() !== null) {
            return false;
        }

        return $projection->lockedUntil() < $this->clock->generate();
    }

    private function assertDataInstance(ProjectionData $data, string $expected): void
    {
        if (! $data instanceof $expected) {
            throw new InvalidArgumentException(sprintf('Invalid data provided, expected class %s', $expected));
        }
    }

    private function determineKey(string $projectionName): string
    {
        return sprintf('%s:%s', self::PROJECTION_PREFIX_KEY, $projectionName);
    }
}
