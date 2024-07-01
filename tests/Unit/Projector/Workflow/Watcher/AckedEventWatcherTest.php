<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow\Watcher;

use Storm\Projector\Workflow\Watcher\AckedEventWatcher;

use function method_exists;

beforeEach(function () {
    $this->watcher = new AckedEventWatcher();
});

function assertAckedStreamEmpty(AckedEventWatcher $watcher, string ...$eventClass): void
{
    expect($watcher->hasEvents())->toBeFalse()
        ->and($watcher->events())->not()->toContain($eventClass)
        ->and($watcher->events())->toBeEmpty()
        ->and($watcher->countUnique())->toBe(0)
        ->and($watcher->count())->toBe(0);
}

test('default instance', function () {
    assertAckedStreamEmpty($this->watcher, 'event-1');

    expect(method_exists($this->watcher, 'subscribe'))->toBeFalse();
});

test('ack event', function () {
    $this->watcher->ack('event-1');

    expect($this->watcher->hasEvents())->toBeTrue()
        ->and($this->watcher->events())->toBe(['event-1']);
});

test('ack many events', function () {
    $this->watcher->ack('event-1');
    $this->watcher->ack('event-2');

    expect($this->watcher->hasEvents())->toBeTrue()
        ->and($this->watcher->events())->toBe(['event-1', 'event-2'])
        ->and($this->watcher->countUnique())->toBe(2)
        ->and($this->watcher->count())->toBe(2);
});

test('does not duplicate acked events', function () {
    $this->watcher->ack('event-1');
    $this->watcher->ack('event-1');

    expect($this->watcher->hasEvents())->toBeTrue()
        ->and($this->watcher->events())->toBe(['event-1'])
        ->and($this->watcher->countUnique())->toBe(1)
        ->and($this->watcher->count())->toBe(2);
});

test('reset acked events', function () {
    $this->watcher->ack('event-1');
    $this->watcher->ack('event-2');

    expect($this->watcher->hasEvents())->toBeTrue()
        ->and($this->watcher->events())->toBe(['event-1', 'event-2'])
        ->and($this->watcher->countUnique())->toBe(2)
        ->and($this->watcher->count())->toBe(2);

    $this->watcher->reset();

    assertAckedStreamEmpty($this->watcher, 'event-1', 'event-2');
});
