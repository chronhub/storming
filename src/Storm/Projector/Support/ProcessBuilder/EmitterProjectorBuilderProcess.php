<?php

declare(strict_types=1);

namespace Storm\Projector\Support\ProcessBuilder;

use Storm\Contract\Projector\EmitterProjector;

final class EmitterProjectorBuilderProcess extends ProjectionBuilderProcess
{
    private ?EmitterProjector $projector = null;

    public function build(): EmitterProjector
    {
        $emitterProjector = $this->projectorManager->newEmitterProjector(
            $this->projectionName,
            $this->option,
            $this->connection
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
