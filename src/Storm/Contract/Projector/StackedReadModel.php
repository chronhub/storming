<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

interface StackedReadModel
{
    /**
     * Stacks an operation to be applied to the read model.
     */
    public function stack(string $method, mixed ...$arguments): void;
}
