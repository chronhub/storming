<?php

declare(strict_types=1);

namespace Storm\Projector\Support\ProcessBuilder;

use Storm\Contract\Projector\QueryProjector;

final class QueryProjectorBuilderProcess extends ProjectionBuilderProcess
{
    private ?QueryProjector $projector = null;

    public function build(): QueryProjector
    {
        $queryProjector = $this->projectorManager->newQueryProjector(
            $this->option,
            $this->connection
        );

        return $this->projector = $this->buildProjector($queryProjector);
    }

    public function run(bool $keepRunning = false): void
    {
        if (! $this->projector instanceof QueryProjector) {
            $this->projector = $this->build();
        }

        $this->projector->run($keepRunning);
    }
}
