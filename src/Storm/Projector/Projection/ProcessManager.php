<?php

declare(strict_types=1);

namespace Storm\Projector\Projection;

use Illuminate\Pipeline\Pipeline;
use Storm\Contract\Projector\ContextReader;
use Storm\Projector\Exception\ProjectionAlreadyRunning;
use Storm\Projector\Factory\Activity\ActivityFactory;
use Storm\Projector\Factory\Activity\PersistentActivityFactory;
use Storm\Projector\Projection\Events\ProjectionFreed;
use Storm\Projector\Workflow\Input\RestoreUserState;
use Storm\Projector\Workflow\Process;
use Throwable;

final class ProcessManager implements Manager
{
    private Pipeline $pipeline;

    public function __construct(
        private readonly Process $process,
        private readonly ActivityFactory $activityFactory,
    ) {
        $this->pipeline = new Pipeline;
    }

    public function start(ContextReader $context, bool $keepRunning): void
    {
        $this->prepare($context, $keepRunning);

        $this->pipeline->through(
            ($this->activityFactory)($this->process)
        );

        $this->execute();
    }

    public function call(callable $callback): mixed
    {
        return $callback($this->process);
    }

    private function prepare(ContextReader $context, bool $keepRunning): void
    {
        // Configure context
        $this->process->context()->set($context);
        $this->process->call(new RestoreUserState);

        // Subscribe the process to the context
        $this->process->subscribe($this->process, $context);

        // Configure sprint
        $this->process->sprint()->runInBackground($keepRunning);
        $this->process->sprint()->continue();
    }

    private function execute(): void
    {
        $exceptionOccurred = null;

        try {
            $this->loop();
        } catch (Throwable $exception) {
            $exceptionOccurred = $exception;
        } finally {
            $this->releaseProcess($exceptionOccurred);
        }
    }

    private function loop(): void
    {
        do {
            $isSprintTerminated = $this->pipeline
                ->send($this->process)
                ->then(fn (Process $process): bool => $process->isSprintTerminated());
        } while (! $isSprintTerminated);
    }

    /**
     * When a process is terminated, we attempt to release the lock.
     *
     * @throws Throwable
     */
    private function releaseProcess(?Throwable $exception): void
    {
        // Prevent freed a persistent projection when another instance
        // is running, or the lock is still active.
        if ($exception instanceof ProjectionAlreadyRunning) {
            throw $exception;
        }

        try {
            if ($exception) {
                throw $exception;
            }
        } finally {
            if ($this->activityFactory instanceof PersistentActivityFactory) {
                $this->process->dispatch(new ProjectionFreed);
            }
        }
    }
}
