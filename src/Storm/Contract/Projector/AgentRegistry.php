<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

use Storm\Projector\Workflow\Agent\ContextReaderAgent;
use Storm\Projector\Workflow\Agent\EventStreamDiscoveryAgent;
use Storm\Projector\Workflow\Agent\ProcessedStreamAgent;
use Storm\Projector\Workflow\Agent\ProjectionStatusAgent;
use Storm\Projector\Workflow\Agent\ReportAgent;
use Storm\Projector\Workflow\Agent\SprintAgent;
use Storm\Projector\Workflow\Agent\StatAgent;
use Storm\Projector\Workflow\Agent\StopAgent;
use Storm\Projector\Workflow\Agent\StreamEventAgent;
use Storm\Projector\Workflow\Agent\TimeAgent;
use Storm\Projector\Workflow\Agent\UserStateAgent;

/**
 * @method ContextReaderAgent        context()
 * @method EventStreamDiscoveryAgent discovery()
 * @method ProjectionOption          option()
 * @method ProcessedStreamAgent      processedStream()
 * @method CheckpointRecognition     recognition()
 * @method ReportAgent               report()
 * @method SprintAgent               sprint()
 * @method StatAgent                 stat()
 * @method ProjectionStatusAgent     status()
 * @method StopAgent                 stop()
 * @method StreamEventAgent          streamEvent()
 * @method TimeAgent                 time()
 * @method UserStateAgent            userState()
 */
interface AgentRegistry
{
    /**
     * Create a new workflow.
     */
    public function newWorkflow(): WorkflowInterface;

    /**
     * Capture event and return the result if it can apply.
     *
     * @param callable(self): mixed|object $event
     */
    public function capture(callable|object $event): mixed;

    /**
     * Subscribe to the notification hub.
     */
    public function subscribe(NotificationHub $hub, ContextReader $context): void;
}
