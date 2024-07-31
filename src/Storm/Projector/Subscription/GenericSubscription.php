<?php

declare(strict_types=1);

namespace Storm\Projector\Subscription;

use Storm\Contract\Projector\ActivityFactory;
use Storm\Contract\Projector\ContextReader;
use Storm\Contract\Projector\PersistentActivityFactory;
use Storm\Contract\Projector\Subscriptor;
use Storm\Contract\Projector\WorkflowInterface;
use Storm\Projector\Exception\ProjectionAlreadyRunning;
use Storm\Projector\Workflow\Input\RestoreUserState;
use Storm\Projector\Workflow\Management\ProjectionFreed;
use Storm\Projector\Workflow\Process;
use Storm\Projector\Workflow\Workflow;
use Throwable;

/**
 * @phpstan-import-type TExceptionHandler from WorkflowInterface
 */
final readonly class GenericSubscription implements Subscriptor
{
    public function __construct(
        protected Process $process,
        protected ActivityFactory $activityFactory,
    ) {}

    public function start(ContextReader $context, bool $keepRunning): void
    {
        $this->newWorkflow($context, $keepRunning)->execute();
    }

    public function call(callable $callback): mixed
    {
        return $callback($this->process);
    }

    private function newWorkflow(ContextReader $context, bool $keepRunning): WorkflowInterface
    {
        $this->prepare($context, $keepRunning);

        $activities = ($this->activityFactory)($this->process);

        return $this->create($activities);
    }

    private function create(array $activities): WorkflowInterface
    {
        $workflow = Workflow::create($this->process, $activities);

        if ($this->activityFactory instanceof PersistentActivityFactory) {
            $exceptionHandler = $this->persistentExceptionHandler();

            $workflow->withExceptionHandler($exceptionHandler);
        }

        return $workflow;
    }

    private function prepare(ContextReader $context, bool $keepRunning): void
    {
        $this->process->context()->set($context);
        $this->process->call(new RestoreUserState());

        $this->process->subscribe($this->process, $context);

        $this->process->sprint()->runInBackground($keepRunning);
        $this->process->sprint()->continue();
    }

    /**
     * Returns the exception handler for persistent projection.
     *
     * @return TExceptionHandler
     */
    private function persistentExceptionHandler(): callable
    {
        return function (Process $process, ?Throwable $exception): void {
            if ($exception instanceof ProjectionAlreadyRunning) {
                throw $exception;
            }

            try {
                $process->dispatch(new ProjectionFreed());
            } catch (Throwable) {
                // ignore
            }

            if ($exception) {
                throw $exception;
            }
        };
    }
}
