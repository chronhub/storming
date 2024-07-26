<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow;

use Closure;
use Storm\Contract\Projector\NotificationHub;
use Storm\Contract\Projector\WorkflowInterface;
use Storm\Projector\Exception\RuntimeException;
use Storm\Projector\Workflow\Notification\Promise\IsSprintTerminated;
use Throwable;

use function array_reduce;
use function array_reverse;
use function in_array;

final class Workflow implements WorkflowInterface
{
    /**
     * @var callable(NotificationHub, ?Throwable): void|null
     */
    protected $exceptionHandler = null;

    /**
     * Prevent the same instance from running again,
     * after an exception has occurred.
     */
    protected ?Throwable $exceptionOccurred = null;

    /**
     * List of ignored exceptions which allow the projection to run again.
     *
     * @var array|array<class-string>
     *
     * todo WorkflowBuilder
     */
    protected array $ignoredExceptions = [];

    /** @param array<callable(NotificationHub, callable): bool|callable> $activities */
    protected function __construct(
        protected NotificationHub $hub,
        protected Stage $stage,
        protected array $activities,
        array $ignoredExceptions = []
    ) {
        $this->ignoredExceptions = $ignoredExceptions;
    }

    /**
     * Creates a new workflow.
     */
    public static function create(NotificationHub $hub, Stage $stage, array $activities): self
    {
        return new self($hub, $stage, $activities);
    }

    /**
     * Processes the workflow.
     *
     * @throws RuntimeException when an exception has occurred in a previous run
     * @throws Throwable        when any other exception occurs
     */
    public function process(?callable $destination = null): void
    {
        $this->assertNoPreviousException();

        $process = $this->prepareProcess();

        try {
            do {
                $this->stage->beforeProcessing($this->hub);

                $process($this->hub);

                $this->stage->afterProcessing($this->hub);
            } while (! $this->hub->await(IsSprintTerminated::class));
        } catch (Throwable $exception) {
            $this->exceptionOccurred = $exception;
        } finally {
            $this->handleException($this->exceptionOccurred);
        }
    }

    /**
     * Sets the workflow exception handler.
     */
    public function withExceptionHandler(callable $exceptionHandler): self
    {
        if (! $this->exceptionHandler) {
            $this->exceptionHandler = $exceptionHandler;
        }

        return $this;
    }

    /**
     * Prepares the process closure.
     *
     * Ignore return of the workflow process
     * as it may have returned a boolean value early
     */
    protected function prepareProcess(): Closure
    {
        return array_reduce(
            array_reverse($this->activities),
            fn (callable $stack, callable $activity) => fn (NotificationHub $hub) => $activity($hub, $stack),
            fn () => false
        );
    }

    /**
     * Handles the exception.
     *
     * @throws Throwable
     */
    private function handleException(?Throwable $exception): void
    {
        if ($this->exceptionHandler) {
            ($this->exceptionHandler)($this->hub, $exception);
        } elseif ($exception) {
            throw $exception;
        }
    }

    /**
     * Asserts that the projection is not running again when an exception has occurred.
     */
    private function assertNoPreviousException(): void
    {
        if ($this->exceptionOccurred === null) {
            return;
        }

        if (in_array($this->exceptionOccurred::class, $this->ignoredExceptions)) {
            return;
        }

        $message = 'Running the projection again is not allowed ';
        $message .= 'when an exception has occurred within a same workflow instance ';
        $message .= 'in the previous run.';

        throw new RuntimeException($message);
    }
}
