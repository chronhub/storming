<?php

declare(strict_types=1);

namespace Storm\Projector\Factory;

use Storm\Contract\Projector\ActivityFactory;
use Storm\Contract\Projector\AgentRegistry;
use Storm\Contract\Projector\NotificationHub;
use Storm\Contract\Projector\PersistentActivityFactory;
use Storm\Contract\Projector\WorkflowInterface;
use Storm\Projector\Exception\ProjectionAlreadyRunning;
use Storm\Projector\Workflow\Notification\Management\ProjectionFreed;
use Storm\Projector\Workflow\Stage;
use Storm\Projector\Workflow\Workflow;
use Throwable;

class WorkflowBuilder
{
    public function __construct(
        protected NotificationHub $hub,
        protected ActivityFactory $activityFactory,
        protected Stage $stage,
    ) {}

    public function create(AgentRegistry $registry): WorkflowInterface
    {
        $activities = ($this->activityFactory)($registry);

        $workflow = Workflow::create($this->hub, $this->stage, $activities);

        if ($this->activityFactory instanceof PersistentActivityFactory) {
            $exceptionHandler = $this->persistentExceptionHandler();

            $workflow->withExceptionHandler($exceptionHandler);
        }

        return $workflow;
    }

    /**
     * Returns the exception handler for persistent projections.
     *
     * @return callable(NotificationHub, ?Throwable): void
     */
    protected function persistentExceptionHandler(): callable
    {
        return function (NotificationHub $hub, ?Throwable $exception): void {
            if ($exception instanceof ProjectionAlreadyRunning) {
                throw $exception;
            }

            try {
                $hub->emit(new ProjectionFreed());
            } catch (Throwable) {
                // ignore
            }

            if ($exception) {
                throw $exception;
            }
        };
    }
}
