<?php

declare(strict_types=1);

namespace Storm\Tests\Domain;

use Storm\Clock\PointInTime;
use Storm\Contract\Chronicler\Chronicler;
use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Message\EventHeader;
use Storm\Contract\Message\Header;
use Storm\Stream\Stream;
use Storm\Stream\StreamName;
use Storm\Tests\Domain\Balance\BalanceAdded;
use Storm\Tests\Domain\Balance\BalanceCreated;
use Storm\Tests\Domain\Balance\BalanceId;
use Storm\Tests\Domain\Balance\BalanceSubtracted;

class BalanceEventStore
{
    public SystemClock $clock;

    public function __construct(
        public Chronicler $chronicler,
        public StreamName $streamName,
        public BalanceId $balanceId
    ) {
        $this->clock = new PointInTime();
    }

    public function withBalanceCreated(int $version, int $amount = 0): self
    {
        $event = BalanceCreated::withBalance($this->balanceId, $amount)
            ->withHeaders([
                EventHeader::AGGREGATE_ID => $this->balanceId->toString(),
                EventHeader::AGGREGATE_VERSION => $version,
                Header::EVENT_TIME => $this->clock->generate(),
            ]);

        $this->feed($event);

        return $this;
    }

    public function withBalanceAdded(int $version, int $amount): self
    {
        $event = BalanceAdded::withBalance($this->balanceId, $amount)
            ->withHeaders([
                EventHeader::AGGREGATE_ID => $this->balanceId->toString(),
                EventHeader::AGGREGATE_VERSION => $version,
                Header::EVENT_TIME => $this->clock->generate(),
            ]);

        $this->feed($event);

        return $this;
    }

    public function withBalanceSubtracted(int $version, int $amount): self
    {
        $event = BalanceSubtracted::withBalance($this->balanceId, $amount)
            ->withHeaders([
                EventHeader::AGGREGATE_ID => $this->balanceId->toString(),
                EventHeader::AGGREGATE_VERSION => $version,
                Header::EVENT_TIME => $this->clock->generate(),
            ]);

        $this->feed($event);

        return $this;
    }

    public function feed(DomainEvent $event): void
    {
        $stream = new Stream($this->streamName, [$event]);

        $this->chronicler->append($stream);
    }
}
