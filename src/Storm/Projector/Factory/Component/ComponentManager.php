<?php

declare(strict_types=1);

namespace Storm\Projector\Factory\Component;

use Storm\Contract\Projector\ContextReader;
use Storm\Projector\Checkpoint\CheckpointRecognition;
use Storm\Projector\Options\Option;
use Storm\Projector\Workflow\Notifier;
use Storm\Projector\Workflow\Process;

/**
 * @method EventStreamBatch      batch()
 * @method Computation           compute()
 * @method Contextualize         context()
 * @method EventStreamDiscovery  discovery()
 * @method Notifier              dispatcher()
 * @method HaltOn                haltOn()
 * @method Metrics               metrics()
 * @method Option                option()
 * @method CheckpointRecognition recognition()
 * @method ProcessedStream       stream()
 * @method Sprint                sprint()
 * @method StatusHolder          status()
 * @method Timer                 time()
 * @method UserState             userState()
 */
interface ComponentManager
{
    /**
     * Subscribe to the component.
     */
    public function subscribe(Process $process, ContextReader $context): void;

    /**
     * Apply callback to the component.
     *
     * @param callable(ComponentManager): mixed $callback
     */
    public function call(callable $callback): mixed;
}
