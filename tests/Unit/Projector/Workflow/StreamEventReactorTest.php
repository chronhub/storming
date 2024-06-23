<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow;

use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Projector\EmitterScope;
use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Scope\EmitterAccess;
use Storm\Projector\Scope\EventScope;
use Storm\Projector\Scope\UserStateScope;
use Storm\Projector\Workflow\Notification\Management\EventEmitted;
use Storm\Tests\Stubs\Double\Message\SomeEvent;

test('chain', function () {
    $hub = mock(NotificationHub::class);
    $hub->expects('trigger')
        ->withArgs(fn (object $trigger) => $trigger instanceof EventEmitted);

    $clock = mock(SystemClock::class);

    $currentEvent = SomeEvent::fromContent(['foo' => 'bar']);
    $projectorScope = new EmitterAccess($hub, $clock);
    $userStateScope = new UserStateScope(['count' => 0]);

    $eventScope = new EventScope($currentEvent, $projectorScope, $userStateScope);

    // add callback to ack method
    $return = $eventScope->ack(SomeEvent::class)
        ->then(function (SomeEvent $event, EmitterScope $emitter, UserStateScope $userState) {
            $userState
                ->increment(value : 5)
                ->decrement(value : 2)
                ->merge('foo', ['bar' => 'baz'])
                ->upsert('another', ['key' => 'value']);

            $emitter->emit($event);
        });

    expect($return)->toBeNull()
        ->and($userStateScope->state())->toBe([
            'count' => 3,
            'foo' => ['bar' => 'baz'],
            'another' => ['key' => 'value'],
        ]);
});
