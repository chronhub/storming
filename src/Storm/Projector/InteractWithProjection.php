<?php

declare(strict_types=1);

namespace Storm\Projector;

use Closure;
use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Workflow\Notification\Promise\CurrentUserState;
use Storm\Projector\Workflow\Notification\Promise\GetProjectionReport;

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
            fn (NotificationHub $hub): array => $hub->await(CurrentUserState::class)
        );
    }

    public function getReport(): array
    {
        return $this->subscriber->interact(
            fn (NotificationHub $hub) => $hub->await(GetProjectionReport::class)
        );
    }

    protected function describeIfNeeded(): void
    {
        if ($this->context->id() === null) {
            $this->context->withId(static::class);
        }
    }
}
