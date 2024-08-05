<?php

declare(strict_types=1);

namespace Storm\Projector\Scope;

use Storm\Contract\Projector\StackedReadModel;

interface ReadModelScope extends PersistentProjectorScope
{
    /**
     * Return the read model instance.
     *
     * fixMe do we need to access the full read model inside reactors?
     */
    public function readModel(): StackedReadModel;

    /**
     * Shortcut to read model stack.
     */
    public function stack(string $operation, mixed ...$arguments): self;
}
