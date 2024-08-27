<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

use Storm\Projector\Options\Option;

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
     * @param array<Option::*, null|string|int|bool|array> $options
     */
    public function readModel(
        string $streamName,
        ReadModel $readModel,
        array $options = [],
        ?string $connection = null,
    ): ReadModelProjector;

    /**
     * Monitor the state of the projections.
     */
    public function monitor(?string $connection = null): ProjectorMonitor;
}
