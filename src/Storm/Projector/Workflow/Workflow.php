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

    /**
     * Prevent the same instance from running again,
     * after an exception has occurred.
     */
    private ?Throwable $exceptionOccurred = null;

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
        $this->assertNoPreviousException();

        try {
            $this->loop();
        } catch (Throwable $exception) {
            $this->exceptionOccurred = $exception;
        } finally {
            $this->handleException($this->exceptionOccurred);
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
            // to avoid unexpected behavior, interface activity
            if (! $activity($this->workflowContext)) {
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

    /**
     * Asserts that the projection is not running again when an exception has occurred.
     */
    private function assertNoPreviousException(): void
    {
        if ($this->exceptionOccurred !== null) {
            $message = 'Running the projection again is not allowed ';
            $message .= 'when an exception has occurred within a same workflow instance ';
            $message .= 'in the previous run.';

            throw new RuntimeException($message);
        }
    }
}
