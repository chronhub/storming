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

    /**
     * Proxy method to keep the user state in memory.
     *
     * @see Context::withKeepState()
     */
    public function keepState(): static;
}
