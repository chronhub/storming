<?php

declare(strict_types=1);

namespace Storm\Projector\Support\Builder;

use Illuminate\Contracts\Foundation\Application;
use Storm\Contract\Projector\ProjectorManager;
use Storm\Contract\Projector\QueryProjector;
use Storm\Projector\Connector\ConnectorManager;

class QueryProjectorBuilder
{
    use ProjectorBuilder;

    public ?QueryProjector $projector = null;

    public function __construct(
        protected ConnectorManager $projectorManagement,
        protected ProjectorManager $projectorManager,
        protected Application $app,
    ) {}

    public function build(): QueryProjector
    {
        $queryProjector = $this->projectorManager->query(
            $this->options,
            $this->getConnectionName()
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
