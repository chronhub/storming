<?php

declare(strict_types=1);

namespace Storm\Projector\Scope;

use Storm\Contract\Projector\ReadModel;

interface ReadModelScope extends PersistentProjectorScope
{
    /**
     * Return the read model instance.
     *
     * fixMe doc mixin
     */
    public function readModel(): ReadModel;
}
