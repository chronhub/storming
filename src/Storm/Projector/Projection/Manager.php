<?php

declare(strict_types=1);

namespace Storm\Projector\Projection;

use Storm\Contract\Projector\ContextReader;
use Storm\Projector\Workflow\Process;
use Throwable;

interface Manager
{
    /**
     * Start the projection.
     *
     * @throws Throwable
     */
    public function start(ContextReader $context, bool $keepRunning): void;

    /**
     * Interact with the projection.
     *
     * @param callable(Process): mixed $callback
     */
    public function call(callable $callback): mixed;
}
