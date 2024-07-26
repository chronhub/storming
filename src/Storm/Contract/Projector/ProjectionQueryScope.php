<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

interface ProjectionQueryScope
{
    /**
     * A projection query filter to read events from included position.
     */
    public function fromIncludedPosition(): ProjectionQueryFilter;
}
