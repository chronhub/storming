<?php

declare(strict_types=1);

namespace Storm\Aggregate;

use Storm\Contract\Aggregate\AggregateRoot;
use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Message\EventHeader;
use Storm\Contract\Message\MessageDecorator;
use Storm\Message\Message;

use function array_map;
use function count;
use function reset;

final readonly class AggregateEventReleaser
{
    public function __construct(private MessageDecorator $messageDecorator)
    {
    }

    /**
     * @return array<DomainEvent>|array
     */
    public function release(AggregateRoot $aggregate): array
    {
        $events = $aggregate->releaseEvents();

        if (! reset($events)) {
            return [];
        }

        $version = $aggregate->version() - count($events);

        return $this->decoratedEvents($aggregate, $version, $events);
    }

    /**
     * @param  array<DomainEvent> $events
     * @param  positive-int       $version
     * @return array<DomainEvent>
     */
    private function decoratedEvents(AggregateRoot $aggregate, int $version, array $events): array
    {
        $headers = [
            EventHeader::AGGREGATE_ID => $aggregate->identity()->toString(),
            EventHeader::AGGREGATE_ID_TYPE => $aggregate->identity()::class,
            EventHeader::AGGREGATE_TYPE => $aggregate::class,
        ];

        return array_map(function (DomainEvent $event) use ($headers, &$version) {
            return $this->messageDecorator->decorate(
                new Message($event, $headers + [EventHeader::AGGREGATE_VERSION => ++$version])
            )->event();
        }, $events);
    }
}
