<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

use Storm\Projector\Workflow\Component\Computation;
use Storm\Projector\Workflow\Component\Contextualize;
use Storm\Projector\Workflow\Component\EventStreamBatch;
use Storm\Projector\Workflow\Component\EventStreamDiscovery;
use Storm\Projector\Workflow\Component\HaltOn;
use Storm\Projector\Workflow\Component\Metrics;
use Storm\Projector\Workflow\Component\ProcessedStream;
use Storm\Projector\Workflow\Component\Runner;
use Storm\Projector\Workflow\Component\StatusHolder;
use Storm\Projector\Workflow\Component\Timing;
use Storm\Projector\Workflow\Component\UserState;
use Storm\Projector\Workflow\Process;

/**
 * @method Contextualize         context()
 * @method EventStreamBatch      batch()
 * @method EventStreamDiscovery  discovery()
 * @method EventManager          dispatcher()
 * @method HaltOn                haltOn()
 * @method ProjectionOption      option()
 * @method ProcessedStream       stream()
 * @method CheckpointRecognition recognition()
 * @method Computation           compute()
 * @method Runner                sprint()
 * @method Metrics               metrics()
 * @method StatusHolder          status()
 * @method Timing                time()
 * @method UserState             userState()
 */
interface Component
{
    /**
     * Subscribe to the component.
     */
    public function subscribe(Process $process, ContextReader $context): void;

    /**
     * Apply callback to the component.
     *
     * @param callable(Component): mixed $callback
     */
    public function call(callable $callback): mixed;
}
