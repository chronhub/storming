<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

use Storm\Projector\Options\ProjectionOption;

interface ProjectorManagerInterface
{
    /**
     * Create a new query projector.
     *
     * @param array<ProjectionOption::*, null|string|int|bool|array> $options
     */
    public function newQueryProjector(array $options = [], ?string $connection = null): QueryProjector;

    /**
     * Create a new emitter projector.
     *
     * @param array<ProjectionOption::*, null|string|int|bool|array> $options
     */
    public function newEmitterProjector(string $streamName, array $options = [], ?string $connection = null): EmitterProjector;

    /**
     * Create a new read model projector.
     *
     * @param array<ProjectionOption::*, null|string|int|bool|array> $options
     */
    public function newReadModelProjector(
        string $streamName,
        ReadModel $readModel,
        array $options = [],
        ?string $connection = null,
    ): ReadModelProjector;

    /**
     * Get the projector monitor.
     */
    public function monitor(?string $connection = null): ProjectorMonitorInterface;
}
