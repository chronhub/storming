<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow;

use Storm\Contract\Projector\ContextReader;
use Storm\Projector\Checkpoint\CheckpointRecognition;
use Storm\Projector\Options\Option;
use Storm\Projector\Workflow\Component\Computation;
use Storm\Projector\Workflow\Component\Contextualize;
use Storm\Projector\Workflow\Component\EventStreamBatch;
use Storm\Projector\Workflow\Component\EventStreamDiscovery;
use Storm\Projector\Workflow\Component\HaltOn;
use Storm\Projector\Workflow\Component\Metrics;
use Storm\Projector\Workflow\Component\ProcessedStream;
use Storm\Projector\Workflow\Component\Runner;
use Storm\Projector\Workflow\Component\StatusHolder;
use Storm\Projector\Workflow\Component\Timer;
use Storm\Projector\Workflow\Component\UserState;

/**
 * @method Contextualize         context()
 * @method EventStreamBatch      batch()
 * @method EventStreamDiscovery  discovery()
 * @method Notifier              dispatcher()
 * @method HaltOn                haltOn()
 * @method Option                option()
 * @method ProcessedStream       stream()
 * @method CheckpointRecognition recognition()
 * @method Computation           compute()
 * @method Runner                sprint()
 * @method Metrics               metrics()
 * @method StatusHolder          status()
 * @method Timer                 time()
 * @method UserState             userState()
 */
interface ComponentRegistry
{
    /**
     * Subscribe to the component.
     */
    public function subscribe(Process $process, ContextReader $context): void;

    /**
     * Apply callback to the component.
     *
     * @param callable(ComponentRegistry): mixed $callback
     */
    public function call(callable $callback): mixed;
}
