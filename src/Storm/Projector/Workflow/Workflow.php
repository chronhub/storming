<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow;

use Throwable;

/**
 * @phpstan-import-type TExceptionHandler from WorkflowInterface
 */
final class Workflow implements WorkflowInterface
{
    /** @var TExceptionHandler|callable|null */
    private $exceptionHandler = null;

    /** @param array<callable(Process): mixed> $activities */
    protected function __construct(
        private Process $process,
        private readonly array $activities,
        private readonly Stage $stage,
    ) {}

    /**
     * Creates a new workflow.
     */
    public static function create(Process $process, array $activities): self
    {
        $stage = new Stage($process);

        return new self($process, $activities, $stage);
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

    public function withExceptionHandler(callable $exceptionHandler): self
    {
        if (! $this->exceptionHandler) {
            $this->exceptionHandler = $exceptionHandler;
        }

        return $this;
    }

    private function loop(): void
    {
        do {
            $this->stage->beforeProcessing();

            $this->run();

            $this->stage->afterProcessing();
        } while (! $this->process->isSprintTerminated());
    }

    private function run(): void
    {
        foreach ($this->activities as $activity) {
            $shouldKeepRunning = $activity($this->process);

            if ($shouldKeepRunning === false) {
                break;
            }
        }
    }

    /**
     * Handles the exception.
     *
     * @throws Throwable
     */
    private function handleException(?Throwable $exception): void
    {
        if (! $exception) {
            return;
        }

        $this->exceptionHandler
            ? ($this->exceptionHandler)($this->process, $exception)
            : throw $exception;
    }
}
