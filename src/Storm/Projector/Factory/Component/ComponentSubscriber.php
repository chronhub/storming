<?php

declare(strict_types=1);

namespace Storm\Projector\Factory\Component;

use Storm\Contract\Projector\ContextReader;
use Storm\Projector\Workflow\Process;

interface ComponentSubscriber
{
    /**
     * Subscribe to the process.
     */
    public function subscribe(Process $process, ContextReader $context): void;
}
