<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Scope;

use stdClass;
use Storm\Contract\Message\DomainEvent;
use Storm\Projector\Scope\AccessScope;

use function abs;

test('default instance', function () {
    $scope = new AccessScope(mock(DomainEvent::class), []);

    expect($scope->state())->toBe([])
        ->and($scope->event())->toBeNull()
        ->and($scope->isAcked())->toBeFalse();
});

test('default instance with state', function (?array $state) {
    $scope = new AccessScope(mock(DomainEvent::class), $state);

    expect($scope->state())->toBe($state);
})->with([
    'null state' => [null],
    'empty state' => [[]],
    'state with data' => [['key' => 'value']],
]);

test('ack event', function () {
    $event = mock(DomainEvent::class);
    $scope = new AccessScope($event, []);

    expect($scope->isAcked())->toBeFalse()
        ->and($scope->event())->toBeNull();

    $return = $scope->ack($event::class);

    expect($scope->isAcked())->toBeTrue()
        ->and($scope->event())->toBe($event)
        ->and($return)->toBe($scope);
});

test('ack same event again', function () {
    $event = mock(DomainEvent::class);
    $scope = new AccessScope($event, []);

    expect($scope->isAcked())->toBeFalse()
        ->and($scope->event())->toBeNull();

    $return = $scope->ack($event::class);

    expect($scope->isAcked())->toBeTrue()
        ->and($scope->event())->toBe($event)
        ->and($return)->toBe($scope);

    $return = $scope->ack($event::class);

    expect($scope->isAcked())->toBeTrue()
        ->and($scope->event())->toBe($event)
        ->and($return)->toBe($scope);
});

test('ack event with one of', function () {
    $event = mock(DomainEvent::class);
    $scope = new AccessScope($event, []);

    expect($scope->isAcked())->toBeFalse()
        ->and($scope->event())->toBeNull();

    $return = $scope->ackOneOf(stdClass::class, $event::class);

    expect($scope->isAcked())->toBeTrue()
        ->and($scope->event())->toBe($event)
        ->and($return)->toBe($scope);
});

test('ack same event with one of again', function () {
    $event = mock(DomainEvent::class);
    $scope = new AccessScope($event, []);

    expect($scope->isAcked())->toBeFalse()
        ->and($scope->event())->toBeNull();

    $return = $scope->ackOneOf(stdClass::class, $event::class);

    expect($scope->isAcked())->toBeTrue()
        ->and($scope->event())->toBe($event)
        ->and($return)->toBe($scope);

    $return = $scope->ackOneOf($event::class, stdClass::class);

    expect($scope->isAcked())->toBeTrue()
        ->and($scope->event())->toBe($event)
        ->and($return)->toBe($scope);
});

test('match event', function () {
    $event = mock(DomainEvent::class);
    $scope = new AccessScope($event, []);

    expect($scope->match($event::class))->toBeTrue()
        ->and($scope->match(stdClass::class))->toBeFalse();
});

test('does not upsert if increment is true and value is not integer', function () {
    $scope = new AccessScope(mock(DomainEvent::class), ['key' => 'value']);

    $return = $scope->upsert('key', 'some_value', true);

    expect($return)->toBe($scope)
        ->and($scope->state())->toBe(['key' => 'value']);
});

test('insert when key does not exists in state', function () {
    $scope = new AccessScope(mock(DomainEvent::class), ['key' => 'value']);

    expect($scope->state())->toBe(['key' => 'value']);

    $return = $scope->upsert('unknown', 'new value');

    expect($return)->toBe($scope)
        ->and($scope->state())->toBe(['key' => 'value', 'unknown' => 'new value']);
});

test('insert when key does not exists in state with increment', function () {
    $scope = new AccessScope(mock(DomainEvent::class), ['key' => 1]);

    $return = $scope->upsert('unknown', 2, true);

    expect($return)->toBe($scope)
        ->and($scope->state())->toBe(['key' => 1, 'unknown' => 2]);
});

test('update if key exist', function (null|int|string|bool|array $value) {
    $scope = new AccessScope(mock(DomainEvent::class), ['key' => 'value']);

    expect($scope->state())->toBe(['key' => 'value']);

    $return = $scope->upsert('key', $value);

    expect($return)->toBe($scope)
        ->and($scope->state())->toBe(['key' => $value]);
})
    ->with([
        'null value' => [null],
        'boolean value' => [true, false],
        'integer value' => [0, 5, 10],
        'string value' => ['some_value'],
        'array value' => [['some_value']],
    ]);

test('update if key exist with increment', function () {
    $scope = new AccessScope(mock(DomainEvent::class), ['key' => 1]);

    $return = $scope->upsert('key', 2, true);

    expect($return)->toBe($scope)
        ->and($scope->state())->toBe(['key' => 3]);
});

test('merge array if key exist', function () {
    $scope = new AccessScope(mock(DomainEvent::class), ['key' => ['value']]);

    expect($scope->state())->toBe(['key' => ['value']]);

    $return = $scope->merge('key', ['new value']);

    expect($return)->toBe($scope)
        ->and($scope->state())->toBe(['key' => ['value', 'new value']]);
});

test('merge array if key does not exist', function () {
    $scope = new AccessScope(mock(DomainEvent::class), ['some_key' => ['value']]);

    expect($scope->state())->toBe(['some_key' => ['value']]);

    $return = $scope->merge('key', ['new value']);

    expect($return)->toBe($scope)
        ->and($scope->state())->toBe(['some_key' => ['value'], 'key' => ['new value']]);
});

test('decrement key state', function (int $value) {
    $scope = new AccessScope(mock(DomainEvent::class), ['key' => 3]);

    $return = $scope->decrement('key');

    expect($return)->toBe($scope)
        ->and($scope->state())->toBe(['key' => 3 - abs($value)]);
})
    ->with([
        'positive value' => [1, 2],
        'negative value' => [-1, -2],
    ]);

test('does not decrement key state if key does not exist', function () {
    $scope = new AccessScope(mock(DomainEvent::class), ['some_key' => 1]);

    $return = $scope->decrement('key');

    expect($return)->toBe($scope)
        ->and($scope->state())->toBe(['some_key' => 1]);
});
