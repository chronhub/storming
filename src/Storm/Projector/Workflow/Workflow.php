<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow;

use Illuminate\Pipeline\Pipeline;
use Throwable;

/**
 * @phpstan-import-type TExceptionHandler from WorkflowInterface
 */
final class Workflow implements WorkflowInterface
{
    /** @var TExceptionHandler|callable|null */
    private $processReleaser = null;

    private Pipeline $pipeline;

    /** @param array<callable(Process): mixed> $activities */
    protected function __construct(
        private Process $process,
        private readonly array $activities,
    ) {
        $this->pipeline = new Pipeline(app());
    }

    /**
     * Creates a new workflow.
     */
    public static function create(Process $process, array $activities): self
    {
        return new self($process, $activities);
    }

    public function execute(): void
    {
        $exceptionOccurred = null;

        try {
            $this->loop();
        } catch (Throwable $exception) {
            $exceptionOccurred = $exception;
        } finally {
            $this->handleException($exceptionOccurred);
        }
    }

    public function withProcessReleaser(callable $processReleaser): self
    {
        if (! $this->processReleaser) {
            $this->processReleaser = $processReleaser;
        }

        return $this;
    }

    private function loop(): void
    {
        do {
            $isSprintTerminated = $this->pipeline
                ->send($this->process)
                ->through($this->activities)
                ->then(function (Process $process) {
                    return $process->isSprintTerminated();
                });
        } while (! $isSprintTerminated);
    }

    /**
     * Handles the exception.
     *
     * @throws Throwable
     */
    private function handleException(?Throwable $exception): void
    {
        if (! $this->processReleaser && $exception) {
            throw $exception;
        }

        if ($this->processReleaser) {
            ($this->processReleaser)($this->process, $exception);
        }
    }
}
