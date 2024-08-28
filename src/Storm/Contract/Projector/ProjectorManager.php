<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

use Storm\Projector\Options\Option;
use Storm\Projector\Support\ReadModel\ReadModelConnection;

interface ProjectorManager
{
    /**
     * Create a new query projector.
     *
     * @param array<Option::*, null|string|int|bool|array> $options
     */
    public function query(array $options = [], ?string $connection = null): QueryProjector;

    /**
     * Create a new emitter projector.
     *
     * @param array<Option::*, null|string|int|bool|array> $options
     */
    public function emitter(string $streamName, array $options = [], ?string $connection = null): EmitterProjector;

    /**
     * Create a new read model projector.
     *
     * Note that a read model as string can be
     *   - a service registered in the container
     *   - a class implementing the abstract @see ReadModelConnection, or parameterized by a "$connection".
     *     if so, we assume you use the same connection as the event store.
     *   - At last, we just resolved the service through the container.
     *
     * @param array<Option::*, null|string|int|bool|array> $options
     */
    public function readModel(
        string $streamName,
        string|ReadModel $readModel,
        array $options = [],
        ?string $connection = null,
    ): ReadModelProjector;

    /**
     * Monitor the state of the projections.
     */
    public function monitor(?string $connection = null): ProjectorMonitor;
}
