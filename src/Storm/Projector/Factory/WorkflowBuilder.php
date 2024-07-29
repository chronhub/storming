<?php

declare(strict_types=1);

namespace Storm\Projector\Factory;

use Storm\Contract\Projector\ActivityFactory;
use Storm\Contract\Projector\AgentManager;
use Storm\Contract\Projector\ContextReader;
use Storm\Contract\Projector\NotificationHub;
use Storm\Contract\Projector\PersistentActivityFactory;
use Storm\Contract\Projector\WorkflowInterface;
use Storm\Projector\Exception\ProjectionAlreadyRunning;
use Storm\Projector\Workflow\Notification\Command\UserStateRestored;
use Storm\Projector\Workflow\Notification\Management\ProjectionFreed;
use Storm\Projector\Workflow\Stage;
use Storm\Projector\Workflow\Workflow;
use Throwable;

class WorkflowBuilder
{
    public function __construct(
        protected AgentManager $agents,
        protected ActivityFactory $activityFactory,
        protected NotificationHub $hub,
        protected Stage $stage,
    ) {}

    public function newWorkflow(ContextReader $context, bool $keepRunning): WorkflowInterface
    {
        $this->prepare($context, $keepRunning);

        return $this->create($this->agents);
    }

    protected function create(AgentManager $registry): WorkflowInterface
    {
        $activities = ($this->activityFactory)($registry);

        $workflow = Workflow::create($this->hub, $this->stage, $activities);

        if ($this->activityFactory instanceof PersistentActivityFactory) {
            $exceptionHandler = $this->persistentExceptionHandler();

            $workflow->withExceptionHandler($exceptionHandler);
        }

        return $workflow;
    }

    protected function prepare(ContextReader $context, bool $keepRunning): void
    {
        $this->agents->context()->set($context);
        $this->hub->emit(UserStateRestored::class);

        $this->agents->subscribe($this->hub, $context);

        $this->agents->sprint()->runInBackground($keepRunning);
        $this->agents->sprint()->continue();
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
