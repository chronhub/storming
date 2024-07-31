<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Component;

use Storm\Contract\Projector\ContextReader;

class Contextualize
{
    protected ?ContextReader $contextReader = null;

    /**
     * Returns the context reader.
     */
    public function get(): ?ContextReader
    {
        return $this->contextReader;
    }

    /**
     * Sets the context reader.
     */
    public function set(ContextReader $contextReader): void
    {
        $this->contextReader = $contextReader;
    }

    /**
     * Check if the context reader is set.
     */
    public function isset(): bool
    {
        return $this->contextReader instanceof ContextReader;
    }
}
