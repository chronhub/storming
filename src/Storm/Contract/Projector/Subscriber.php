<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

use Closure;
use Storm\Projector\Workflow\WorkflowContext;
use Throwable;

interface Subscriber
{
    /**
     * Start subscription
     *
     * @throws Throwable
     */
    public function start(ContextReader $context, bool $keepRunning): void;

    /**
     * Interact with notification hub
     *
     * @param Closure(WorkflowContext): mixed $callback
     */
    public function interact(Closure $callback): mixed;
}
