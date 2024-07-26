<?php

declare(strict_types=1);

namespace Storm\Tests\Domain;

use Storm\Clock\Clock;
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
        $this->clock = new Clock();
    }

    public function appendOnlyStream(): void
    {
        $this->chronicler->append(new Stream($this->streamName, []));
    }

    public function appendEvent(DomainEvent $event): void
    {
        $stream = new Stream($this->streamName, [$event]);

        $this->chronicler->append($stream);
    }

    /**
     * Appends events with versioning and amount.
     * Array key is the version, array value is the positive or negative amount.
     *
     * @param array<array<positive-int, int>> $values
     */
    public function withVersioningAmount(array $values): self
    {
        foreach ($values as [$version, $amount]) {
            $amount >= 0
                ? $this->withBalanceAdded($version, $amount)
                : $this->withBalanceSubtracted($version, -$amount);
        }

        return $this;
    }

    public function withBalanceCreated(int $version, int $amount = 0): self
    {
        $event = $this->withHeaders(
            BalanceCreated::withBalance($this->balanceId, $amount),
            $version
        );

        $this->appendEvent($event);

        return $this;
    }

    public function withBalanceAdded(int $version, int $amount): self
    {
        $event = $this->withHeaders(
            BalanceAdded::withBalance($this->balanceId, $amount),
            $version
        );

        $this->appendEvent($event);

        return $this;
    }

    public function withBalanceSubtracted(int $version, int $amount): self
    {
        $event = $this->withHeaders(
            BalanceSubtracted::withBalance($this->balanceId, $amount),
            $version
        );

        $this->appendEvent($event);

        return $this;
    }

    protected function withHeaders(DomainEvent $event, int $version): DomainEvent
    {
        return $event->withHeaders([
            EventHeader::AGGREGATE_ID => $this->balanceId->toString(),
            EventHeader::AGGREGATE_VERSION => $version,
            Header::EVENT_TIME => $this->clock->generate(),
        ]);
    }
}
