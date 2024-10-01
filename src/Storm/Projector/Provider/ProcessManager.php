<?php

declare(strict_types=1);

namespace Storm\Projector\Provider;

use Illuminate\Pipeline\Pipeline;
use Storm\Contract\Projector\ContextReader;
use Storm\Projector\Exception\ProjectionAlreadyRunning;
use Storm\Projector\Factory\ActivityFactory;
use Storm\Projector\Provider\Events\ProjectionFreed;
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

        $activities = ($this->activityFactory)($this->process);

        $this->execute($activities);
    }

    public function call(callable $callback): mixed
    {
        return $callback($this->process);
    }

    private function prepare(ContextReader $context, bool $keepRunning): void
    {
        $this->process->context()->set($context);
        $this->process->call(new RestoreUserState);

        $this->process->subscribe($this->process, $context);

        $this->process->sprint()->runInBackground($keepRunning);
        $this->process->sprint()->continue();
    }

    private function execute(array $activities): void
    {
        $exceptionOccurred = null;

        try {
            $this->loop($activities);
        } catch (Throwable $exception) {
            $exceptionOccurred = $exception;
        } finally {
            $this->releaseProcess($exceptionOccurred);
        }
    }

    private function loop(array $activities): void
    {
        do {
            $isSprintTerminated = $this->pipeline
                ->send($this->process)
                ->through($activities)
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
            $this->process->dispatch(new ProjectionFreed);
        }
    }
}
