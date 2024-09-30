<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Chronicler;

use Storm\Chronicler\InMemory\InMemoryEventStreamProvider;
use Storm\Chronicler\InMemory\VersioningEventStore;
use Storm\Clock\ClockFactory;
use Storm\Stream\StreamName;
use Storm\Tests\Domain\Balance\BalanceAdded;
use Storm\Tests\Domain\Balance\BalanceCreated;
use Storm\Tests\Domain\Balance\BalanceId;
use Storm\Tests\Domain\Balance\BalanceSubtracted;
use Storm\Tests\Domain\BalanceEventStore;

test('append stream events', function () {
    $streamName = new StreamName('balance');
    $chronicler = new VersioningEventStore(new InMemoryEventStreamProvider);
    $clock = ClockFactory::create();

    expect($chronicler->hasStream($streamName))->toBeFalse();

    $balanceId = BalanceId::create();
    $store = new BalanceEventStore($chronicler, $clock, $streamName, $balanceId);
    $store
        ->withBalanceCreated(1, 100)
        ->withBalanceAdded(2, 200)
        ->withBalanceSubtracted(3, 150)
        ->withBalanceSubtracted(4, 50);

    expect($chronicler->hasStream($streamName))->toBeTrue();

    $streamEvents = $chronicler->retrieveAll($streamName, $balanceId);

    $eventsOrder = [
        BalanceCreated::class,
        BalanceAdded::class,
        BalanceSubtracted::class,
        BalanceSubtracted::class,
    ];

    while ($streamEvents->valid()) {
        $event = $streamEvents->current();
        expect($event)->toBeInstanceOf($eventsOrder[$streamEvents->key()]);

        $streamEvents->next();
    }

    expect($streamEvents->valid())->toBeFalse()
        ->and($streamEvents->current())->toBeNull()
        ->and($streamEvents->getReturn())->toBe(4);
});
