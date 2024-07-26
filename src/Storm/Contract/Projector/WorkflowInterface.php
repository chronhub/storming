<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

use Throwable;

/**
 * @phpstan-type ExceptionHandler callable(NotificationHub, ?Throwable): void
 */
interface WorkflowInterface
{
    /**
     * Processes the workflow.
     *
     * @throws Throwable
     */
    public function process(?callable $destination = null): void;

    /**
     * Sets the exception handler.
     *
     * @param ExceptionHandler $exceptionHandler
     */
    public function withExceptionHandler(callable $exceptionHandler): WorkflowInterface;
}
