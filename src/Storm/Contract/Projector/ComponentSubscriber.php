<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

use Storm\Projector\Workflow\Process;

interface ComponentSubscriber
{
    public function subscribe(Process $process, ContextReader $context): void;
}
