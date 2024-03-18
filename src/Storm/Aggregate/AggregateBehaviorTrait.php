<?php

declare(strict_types=1);

namespace Storm\Aggregate;

use Generator;
use Storm\Contract\Aggregate\AggregateIdentity;
use Storm\Contract\Message\DomainEvent;

use function end;
use function explode;

trait AggregateBehaviorTrait
{
    private int $version = 0;

    /**
     * @var array<DomainEvent>
     */
    private array $recordedEvents = [];

    protected function __construct(private readonly AggregateIdentity $identity)
    {
    }

    public function identity(): AggregateIdentity
    {
        return $this->identity;
    }

    public function version(): int
    {
        return $this->version;
    }

    public function releaseEvents(): array
    {
        $releasedEvents = $this->recordedEvents;

        $this->recordedEvents = [];

        return $releasedEvents;
    }

    public static function reconstitute(AggregateIdentity $aggregateId, Generator $events): ?static
    {
        $aggregateRoot = new static($aggregateId);

        foreach ($events as $event) {
            $aggregateRoot->apply($event);
        }

        $aggregateRoot->version = (int) $events->getReturn();

        return $aggregateRoot->version() > 0 ? $aggregateRoot : null;
    }

    protected function recordThat(DomainEvent $event): void
    {
        $this->apply($event);

        $this->version++;

        $this->recordedEvents[] = $event;
    }

    protected function apply(DomainEvent $event): void
    {
        $parts = explode('\\', $event::class);

        $this->{'apply'.end($parts)}($event);
    }
}
