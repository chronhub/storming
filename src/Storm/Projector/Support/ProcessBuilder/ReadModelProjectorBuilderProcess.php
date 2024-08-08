<?php

declare(strict_types=1);

namespace Storm\Projector\Support\ProcessBuilder;

use Storm\Contract\Projector\ReadModel;
use Storm\Contract\Projector\ReadModelProjector;

final class ReadModelProjectorBuilderProcess extends ProjectionBuilderProcess
{
    protected ?ReadModelProjector $projector = null;

    private ?ReadModel $readModel = null;

    public function withReadModel(string|ReadModel $readModel): self
    {
        $this->readModel = $readModel;

        return $this;
    }

    public function build(): ReadModelProjector
    {
        $readModelProjector = $this->projectorManager->newReadModelProjector(
            $this->projectionName,
            $this->readModel,
            $this->options,
            $this->connection
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
