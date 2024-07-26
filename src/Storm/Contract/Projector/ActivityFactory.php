<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

use Closure;

interface ActivityFactory
{
    /**
     * Returns an array of callables that will be executed when the projector is running.
     * The callables will be executed in the order they are returned.
     *
     * @return array<Closure>
     */
    public function __invoke(AgentRegistry $agentRegistry): array;
}
