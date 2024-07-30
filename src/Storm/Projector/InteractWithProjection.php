<?php

declare(strict_types=1);

namespace Storm\Projector;

use Closure;
use Storm\Projector\Workflow\WorkflowContext;

trait InteractWithProjection
{
    public function initialize(Closure $userState): static
    {
        $this->context->initialize($userState);

        return $this;
    }

    public function subscribeToStream(string ...$streams): static
    {
        $this->context->subscribeToStream(...$streams);

        return $this;
    }

    public function subscribeToPartition(string ...$partitions): static
    {
        $this->context->subscribeToPartition(...$partitions);

        return $this;
    }

    public function subscribeToAll(): static
    {
        $this->context->subscribeToAll();

        return $this;
    }

    public function when(Closure $reactors): static
    {
        $this->context->when($reactors);

        return $this;
    }

    public function haltOn(Closure $haltOn): static
    {
        $this->context->haltOn($haltOn);

        return $this;
    }

    public function describe(string $id): static
    {
        $this->context->withId($id);

        return $this;
    }

    public function getState(): array
    {
        return $this->subscriber->interact(
            fn (WorkflowContext $workflowContext): array => $workflowContext->userState()->get()
        );
    }

    public function getReport(): array
    {
        return $this->subscriber->interact(
            fn (WorkflowContext $workflowContext) => $workflowContext->report()->getReport()
        );
    }

    protected function describeIfNeeded(): void
    {
        if ($this->context->id() === null) {
            $this->context->withId(static::class);
        }
    }
}
