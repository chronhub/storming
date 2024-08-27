<?php

declare(strict_types=1);

namespace Storm\Projector\Support\Builder;

use Illuminate\Contracts\Foundation\Application;
use Storm\Contract\Projector\ProjectorManager;
use Storm\Contract\Projector\ReadModel;
use Storm\Contract\Projector\ReadModelProjector;
use Storm\Projector\Connector\ConnectorManager;

use function is_string;

class ReadModelProjectorBuilder
{
    use ProjectorBuilder;

    public ?ReadModelProjector $projector = null;

    public null|string|ReadModel $readModel = null;

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

    /**
     * Set the read model for the projection.
     *
     * @return $this
     */
    public function readModel(string|ReadModel $readModel): self
    {
        $this->readModel = $readModel;

        return $this;
    }

    public function build(): ReadModelProjector
    {
        if (is_string($this->readModel)) {
            // fixMe ne need the laravel db configuration
            //  here we use the default
            $this->readModel = $this->app[$this->readModel];
        }

        $readModelProjector = $this->projectorManager->readModel(
            $this->projectionName,
            $this->readModel,
            $this->options,
            $this->getConnectionName()
        );

        return $this->projector = $this->buildProjector($readModelProjector);
    }

    public function run(bool $keepRunning = false): void
    {
        if (! $this->projector instanceof ReadModelProjector) {
            $this->projector = $this->build();
        }

        $this->projector->run($keepRunning);
    }
}
