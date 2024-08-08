<?php

declare(strict_types=1);

namespace Storm\Projector\Support\Builder;

use Storm\Contract\Projector\ReadModel;
use Storm\Contract\Projector\ReadModelProjector;

use function is_string;

final class ReadModelProjectorBuilder extends ProjectorBuilder
{
    public ?ReadModelProjector $projector = null;

    public null|string|ReadModel $readModel = null;

    public function withReadModel(string|ReadModel $readModel): self
    {
        $this->readModel = $readModel;

        return $this;
    }

    public function build(): ReadModelProjector
    {
        if (is_string($this->readModel)) {
            $this->readModel = $this->app[$this->readModel];
        }

        $readModelProjector = $this->projectorManager->newReadModelProjector(
            $this->projectionName,
            $this->readModel,
            $this->option,
            $this->getConnection()
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
