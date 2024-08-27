<?php

declare(strict_types=1);

namespace Storm\Tests\Stubs;

use Storm\Aggregate\AggregateBehaviorTrait;
use Storm\Contract\Aggregate\AggregateIdentity;
use Storm\Contract\Aggregate\AggregateRoot;
use Storm\Tests\Stubs\Double\Message\SomeEvent;

final class AggregateRootStub implements AggregateRoot
{
    use AggregateBehaviorTrait;

    private int $appliesCount = 0;

    public static function create(AggregateIdentity $id): self
    {
        return new self($id);
    }

    public function next(SomeEvent $event): void
    {
        $this->recordThat($event);
    }

    public function getRecordedEvents(): array
    {
        return $this->recordedEvents;
    }

    public function getAppliesCount(): int
    {
        return $this->appliesCount;
    }

    protected function applySomeEvent(SomeEvent $event): void
    {
        $this->appliesCount++;
    }
}
