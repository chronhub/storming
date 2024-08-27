<?php

declare(strict_types=1);

namespace Storm\Projector\Support\Builder;

use Illuminate\Contracts\Foundation\Application;
use Storm\Contract\Projector\EmitterProjector;
use Storm\Contract\Projector\ProjectorManager;
use Storm\Projector\Connector\ConnectorManager;

class EmitterProjectorBuilder
{
    use ProjectorBuilder;

    public ?EmitterProjector $projector = null;

    public function __construct(
        protected ConnectorManager $projectorManagement,
        protected ProjectorManager $projectorManager,
        protected Application $app,
    ) {}

    /**
     * Set the projection name for persistent projections only.
     *
     * @return $this
     */
    public function name(string $projectionName): static
    {
        $this->projectionName = $projectionName;

        return $this;
    }

    public function build(): EmitterProjector
    {
        $emitterProjector = $this->projectorManager->emitter(
            $this->projectionName,
            $this->options,
            $this->getConnectionName()
        );

        return $this->projector = $this->buildProjector($emitterProjector);
    }

    public function run(bool $keepRunning = false): void
    {
        if (! $this->projector instanceof EmitterProjector) {
            $this->projector = $this->build();
        }

        $this->projector->run($keepRunning);
    }
}
