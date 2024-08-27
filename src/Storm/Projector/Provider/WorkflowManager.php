<?php

declare(strict_types=1);

namespace Storm\Projector\Provider;

use Storm\Contract\Projector\ContextReader;
use Storm\Projector\Exception\ProjectionAlreadyRunning;
use Storm\Projector\Factory\ActivityFactory;
use Storm\Projector\Factory\PersistentActivityFactory;
use Storm\Projector\Provider\Events\ProjectionFreed;
use Storm\Projector\Workflow\Input\RestoreUserState;
use Storm\Projector\Workflow\Process;
use Storm\Projector\Workflow\Workflow;
use Storm\Projector\Workflow\WorkflowInterface;
use Throwable;

/**
 * @phpstan-import-type TExceptionHandler from WorkflowInterface
 */
final readonly class WorkflowManager implements Manager
{
    public function __construct(
        private Process $process,
        private ActivityFactory $activityFactory,
    ) {}

    public function start(ContextReader $context, bool $keepRunning): void
    {
        $this->prepare($context, $keepRunning);

        $activities = ($this->activityFactory)($this->process);

        $this->createWorkflow($activities)->execute();
    }

    public function call(callable $callback): mixed
    {
        return $callback($this->process);
    }

    private function createWorkflow(array $activities): WorkflowInterface
    {
        $workflow = Workflow::create($this->process, $activities);

        if ($this->activityFactory instanceof PersistentActivityFactory) {
            $exceptionHandler = $this->getPersistentExceptionHandler();

            $workflow->withExceptionHandler($exceptionHandler);
        }

        return $workflow;
    }

    private function prepare(ContextReader $context, bool $keepRunning): void
    {
        $this->process->context()->set($context);
        $this->process->call(new RestoreUserState);

        $this->process->subscribe($this->process, $context);

        $this->process->sprint()->runInBackground($keepRunning);
        $this->process->sprint()->continue();
    }

    /**
     * Returns the exception handler for persistent projection.
     *
     * @return TExceptionHandler
     */
    private function getPersistentExceptionHandler(): callable
    {
        return function (Process $process, ?Throwable $exception): void {
            // Prevent freed the projection when another instance
            // of the same projection is running, or the lock is still active.
            if ($exception instanceof ProjectionAlreadyRunning) {
                throw $exception;
            }

            try {
                if ($exception) {
                    throw $exception;
                }
            } finally {
                $process->dispatch(new ProjectionFreed);
            }
        };
    }
}
