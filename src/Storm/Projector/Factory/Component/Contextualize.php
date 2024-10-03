<?php

declare(strict_types=1);

namespace Storm\Projector\Factory\Component;

use Storm\Contract\Projector\ContextReader;

class Contextualize
{
    /**
     * The context of the projection.
     */
    protected ?ContextReader $contextReader = null;

    public function get(): ?ContextReader
    {
        return $this->contextReader;
    }

    public function set(ContextReader $contextReader): void
    {
        $this->contextReader = $contextReader;
    }
}
