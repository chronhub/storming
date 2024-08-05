<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Projector\InMemory\Concern;

use Storm\Stream\StreamName;
use Storm\Tests\Domain\Balance\BalanceId;
use Storm\Tests\Domain\BalanceEventStore;
use Storm\Tests\Feature\Projector\InMemory\Factory\InMemoryTestingFactory;

/**
 * @property InMemoryTestingFactory $factory
 */
trait BalanceEventStoreSetupTrait
{
    /**
     * @var array<string, BalanceEventStore>|array
     */
    protected array $eventStore = [];

    protected function makeEventStore(string $streamName, ?BalanceId $balanceId = null): BalanceEventStore
    {
        return $this->eventStore[$streamName] ??= new BalanceEventStore(
            $this->factory->getEventStore(),
            $this->factory->getClock(),
            new StreamName($streamName),
            $balanceId ?? BalanceId::create(),
        );
    }

    protected function balanceEventStore(string $streamName): BalanceEventStore
    {
        return $this->eventStore[$streamName];
    }
}
