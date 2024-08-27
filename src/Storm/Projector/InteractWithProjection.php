<?php

declare(strict_types=1);

namespace Storm\Projector;

use Closure;
use Storm\Contract\Projector\ProjectorFactory;
use Storm\Projector\Support\ProjectionReport;
use Storm\Projector\Workflow\Process;

/**
 * @phpstan-require-implements ProjectorFactory
 */
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

    public function when(array $reactors, ?Closure $then = null): static
    {
        $this->context->when($reactors, $then);

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
        return $this->manager->call(
            fn (Process $process) => $process->userState()->get()
        );
    }

    public function getReport(): ProjectionReport
    {
        return $this->manager->call(
            fn (Process $process) => $process->compute()->report()
        );
    }

    protected function describeIfNeeded(): void
    {
        if ($this->context->id() === null) {
            $this->context->withId(static::class);
        }
    }
}
