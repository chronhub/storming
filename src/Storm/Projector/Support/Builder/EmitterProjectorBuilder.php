<?php

declare(strict_types=1);

namespace Storm\Projector\Support\Builder;

use Storm\Contract\Projector\EmitterProjector;

final class EmitterProjectorBuilder extends ProjectorBuilder
{
    private ?EmitterProjector $projector = null;

    public function build(): EmitterProjector
    {
        $emitterProjector = $this->projectorManager->newEmitterProjector(
            $this->projectionName,
            $this->option,
            $this->getConnection()
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
