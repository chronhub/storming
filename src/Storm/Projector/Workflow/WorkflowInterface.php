<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow;

use Throwable;

/**
 * @phpstan-type TExceptionHandler callable(Process, ?Throwable): void
 */
interface WorkflowInterface
{
    /**
     * Processes the workflow.
     *
     * @throws Throwable
     */
    public function execute(): void;

    /**
     * Sets the exception handler.
     *
     * @param TExceptionHandler $exceptionHandler
     */
    public function withExceptionHandler(callable $exceptionHandler): WorkflowInterface;
}
