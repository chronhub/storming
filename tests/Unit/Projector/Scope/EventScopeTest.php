<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Scope;

use stdClass;
use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Projector\NotificationHub;
use Storm\Contract\Projector\ProjectorScope;
use Storm\Projector\Scope\EventScope;
use Storm\Projector\Scope\UserStateScope;

beforeEach(function () {
    $this->hub = mock(NotificationHub::class);
    $this->clock = mock(SystemClock::class);
    $this->userState = mock(UserStateScope::class);
    $this->projector = mock(ProjectorScope::class);
    $this->event = mock(DomainEvent::class);
    $this->scope = new EventScope($this->event, $this->projector, $this->userState);
});

test('default instance', function () {
    expect($this->scope->projector)->toBe($this->projector)
        ->and($this->scope->userState)->toBe($this->userState)
        ->and($this->scope->isAcked())->toBeFalse()
        ->and($this->scope->event())->toBeNull();
});

test('ack event', function () {
    expect($this->scope->isAcked())->toBeFalse();

    $return = $this->scope->ack($this->event::class);

    expect($this->scope->isAcked())->toBeTrue()
        ->and($this->scope->event())->toBe($this->event)
        ->and($return)->toBe($this->scope);
});

test('ack event again', function () {
    expect($this->scope->isAcked())->toBeFalse();

    $return = $this->scope->ack($this->event::class);

    expect($return)->toBe($this->scope)
        ->and($this->scope->ack($this->event::class))->toBe($return);
});

test('does not ack event', function () {
    expect($this->scope->isAcked())->toBeFalse();

    $return = $this->scope->ack(DomainEvent::class);

    expect($this->scope->isAcked())->toBeFalse()
        ->and($this->scope->event())->toBeNull()
        ->and($return)->toBeNull();
});

test('ack one of events', function () {
    expect($this->scope->isAcked())->toBeFalse();

    $return = $this->scope->ackOneOf(DomainEvent::class, $this->event::class);

    expect($this->scope->isAcked())->toBeTrue()
        ->and($this->scope->event())->toBe($this->event)
        ->and($return)->toBe($this->scope);
});

test('does not ack one of events', function () {
    expect($this->scope->isAcked())->toBeFalse();

    $return = $this->scope->ackOneOf(DomainEvent::class, stdClass::class);

    expect($this->scope->isAcked())->toBeFalse()
        ->and($this->scope->event())->toBeNull()
        ->and($return)->toBeNull();
});

test('ack event and then apply callback', function () {
    $return = $this->scope->ack($this->event::class)
        ->then(function (DomainEvent $event, ProjectorScope $projector, UserStateScope $userState) {
            expect($event)->toBe($this->event)
                ->and($projector)->toBe($this->projector)
                ->and($userState)->toBe($this->userState);

            return -1;
        });

    expect($return)->toBe(-1);
});

test('return instance on then when event is not acked', function () {
    expect($this->scope->then(fn () => -1))->toBe($this->scope);
});

test('match event', function () {
    expect($this->scope->match($this->event::class))->toBeTrue()
        ->and($this->scope->match(stdClass::class))->toBeFalse();
});
