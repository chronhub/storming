<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Component;

use Closure;

class UserState
{
    protected array $state = [];

    /**
     * Initialize the user state.
     */
    public function init(?Closure $original): void
    {
        $this->state = value($original) ?? [];
    }

    /**
     * Put the user state.
     */
    public function put(array $state): void
    {
        $this->state = $state;
    }

    /**
     * Get the user state.
     */
    public function get(): array
    {
        return $this->state;
    }
}
