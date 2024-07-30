<?php

declare(strict_types=1);

namespace Storm\Projector\Subscription;

use Closure;
use Storm\Contract\Projector\ActivityFactory;
use Storm\Contract\Projector\ContextReader;
use Storm\Contract\Projector\PersistentActivityFactory;
use Storm\Contract\Projector\Subscriber;
use Storm\Contract\Projector\WorkflowInterface;
use Storm\Projector\Exception\ProjectionAlreadyRunning;
use Storm\Projector\Exception\RuntimeException;
use Storm\Projector\Workflow\Notification\Management\ProjectionFreed;
use Storm\Projector\Workflow\Stage;
use Storm\Projector\Workflow\Workflow;
use Storm\Projector\Workflow\WorkflowContext;
use Throwable;

final class GenericSubscription implements Subscriber
{
    /**
     * Prevents interaction with the workflow before it has started.
     */
    protected bool $hasStarted = false;

    public function __construct(
        protected readonly WorkflowContext $workflowContext,
        protected readonly ActivityFactory $activityFactory,
        protected readonly Stage $stage,
    ) {}

    public function start(ContextReader $context, bool $keepRunning): void
    {
        $this->hasStarted = true;

        $this->newWorkflow($context, $keepRunning)->process();
    }

    public function interact(Closure $callback): mixed
    {
        if (! $this->hasStarted) {
            throw new RuntimeException('Projection has not started yet');
        }

        return value($callback, $this->workflowContext);
    }

    private function newWorkflow(ContextReader $context, bool $keepRunning): WorkflowInterface
    {
        $this->prepare($context, $keepRunning);

        $activities = ($this->activityFactory)($this->workflowContext);

        return $this->create($activities);
    }

    private function create(array $activities): WorkflowInterface
    {
        $workflow = Workflow::create($this->workflowContext, $this->stage, $activities);

        if ($this->activityFactory instanceof PersistentActivityFactory) {
            $exceptionHandler = $this->persistentExceptionHandler();

            $workflow->withExceptionHandler($exceptionHandler);
        }

        return $workflow;
    }

    private function prepare(ContextReader $context, bool $keepRunning): void
    {
        $this->workflowContext->context()->set($context);
        $this->workflowContext->restoreUserState();

        $this->workflowContext->subscribe($this->workflowContext, $context);

        $this->workflowContext->sprint()->runInBackground($keepRunning);
        $this->workflowContext->sprint()->continue();
    }

    /**
     * Returns the exception handler for persistent projections.
     *
     * @return callable(WorkflowContext, ?Throwable): void
     */
    private function persistentExceptionHandler(): callable
    {
        return function (WorkflowContext $workflowContext, ?Throwable $exception): void {
            if ($exception instanceof ProjectionAlreadyRunning) {
                throw $exception;
            }

            try {
                $workflowContext->emit(new ProjectionFreed());
            } catch (Throwable) {
                // ignore
            }

            if ($exception) {
                throw $exception;
            }
        };
    }
}
