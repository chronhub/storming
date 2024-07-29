<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

use Storm\Contract\Chronicler\QueryFilter;

interface QueryProjectorFactory extends ProjectorFactory
{
    /**
     * Proxy method to set the query filter.
     *
     * @see Context::withQueryFilter()
     */
    public function filter(QueryFilter $queryFilter): static;
}
