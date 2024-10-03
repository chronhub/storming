<?php

declare(strict_types=1);

namespace Storm\Projector\Factory\Activity;

use Storm\Projector\Workflow\Process;

interface ActivityFactory
{
    /**
     * Returns an array of callables that will be executed when the projector is running.
     * The callables will be executed in the order they are returned.
     *
     * @return array<callable>
     */
    public function __invoke(Process $process): array;
}
