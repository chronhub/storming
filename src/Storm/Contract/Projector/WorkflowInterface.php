<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

use Storm\Projector\Workflow\WorkflowContext;
use Throwable;

/**
 * @phpstan-type ExceptionHandler callable(WorkflowContext, ?Throwable): void
 */
interface WorkflowInterface
{
    /**
     * Processes the workflow.
     *
     * @throws Throwable
     */
    public function process(): void;

    /**
     * Sets the exception handler.
     *
     * @param ExceptionHandler $exceptionHandler
     */
    public function withExceptionHandler(callable $exceptionHandler): WorkflowInterface;
}
