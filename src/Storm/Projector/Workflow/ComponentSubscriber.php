<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow;

use Storm\Contract\Projector\ContextReader;

interface ComponentSubscriber
{
    /**
     * Subscribe to the process.
     */
    public function subscribe(Process $process, ContextReader $context): void;
}
