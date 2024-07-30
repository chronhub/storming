<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow;

use Storm\Contract\Projector\WorkflowInterface;
use Storm\Projector\Exception\RuntimeException;
use Throwable;

final class Workflow implements WorkflowInterface
{
    /**
     * @var callable(WorkflowContext, ?Throwable): void|null
     */
    private $exceptionHandler = null;

    /** @param array<callable(WorkflowContext): bool> $activities */
    protected function __construct(
        private readonly WorkflowContext $workflowContext,
        private readonly Stage $stage,
        private readonly array $activities,
    ) {}

    /**
     * Creates a new workflow.
     */
    public static function create(WorkflowContext $workflowContext, Stage $stage, array $activities): self
    {
        return new self($workflowContext, $stage, $activities);
    }

    /**
     * @throws RuntimeException when an exception has occurred in a previous run
     * @throws Throwable        when any other exception occurs
     */
    public function process(): void
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
            $this->stage->beforeProcessing($this->workflowContext);

            $this->run();

            $this->stage->afterProcessing($this->workflowContext);
        } while (! $this->workflowContext->isSprintTerminated());
    }

    private function run(): void
    {
        foreach ($this->activities as $activity) {
            if ($activity($this->workflowContext) === false) {
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
        if ($this->exceptionHandler) {
            ($this->exceptionHandler)($this->workflowContext, $exception);
        } elseif ($exception) {
            throw $exception;
        }
    }
}
