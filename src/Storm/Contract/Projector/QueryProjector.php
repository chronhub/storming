<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

interface QueryProjector extends QueryProjectorFactory
{
    /**
     * Resets the query projection.
     */
    public function reset(): void;
}
