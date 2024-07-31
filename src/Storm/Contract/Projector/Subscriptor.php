<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

use Storm\Projector\Workflow\Process;
use Throwable;

interface Subscriptor
{
    /**
     * Start subscription
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
