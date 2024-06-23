<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Scope;

use stdClass;
use Storm\Projector\Scope\UserStateScope;

use function abs;

test('default instance', function () {
    $scope = new UserStateScope([]);

    expect($scope->state())->toBe([]);
});

test('default instance with state', function (array $state) {
    $scope = new UserStateScope($state);

    expect($scope->state())->toBe($state);
})->with([
    'empty state' => [[]],
    'state with data' => [['key' => 'value']],
]);

test('insert when key does not exists in state', function () {
    $scope = new UserStateScope(['key' => 'value']);

    expect($scope->state())->toBe(['key' => 'value']);

    $return = $scope->upsert('unknown', 'new value');

    expect($return)->toBe($scope)
        ->and($scope->state())->toBe(['key' => 'value', 'unknown' => 'new value']);
});

test('update if key exist', function (mixed $value) {
    $scope = new UserStateScope(['key' => 'value']);

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
        'object value' => [new stdClass()],
    ]);

test('merge array if key exist', function () {
    $scope = new UserStateScope(['key' => ['value']]);

    expect($scope->state())->toBe(['key' => ['value']]);

    $return = $scope->merge('key', ['new value']);

    expect($return)->toBe($scope)
        ->and($scope->state())->toBe(['key' => ['value', 'new value']]);
});

test('merge array if key does not exist', function () {
    $scope = new UserStateScope(['some_key' => ['value']]);

    expect($scope->state())->toBe(['some_key' => ['value']]);

    $return = $scope->merge('key', ['new value']);

    expect($return)->toBe($scope)
        ->and($scope->state())->toBe(['some_key' => ['value'], 'key' => ['new value']]);
});

test('decrement key state', function (int $value) {
    $scope = new UserStateScope(['key' => 3]);

    $return = $scope->decrement('key');

    expect($return)->toBe($scope)
        ->and($scope->state())->toBe(['key' => 3 - abs($value)]);
})
    ->with([
        'positive value' => [1, 2],
        'negative value' => [-1, -2],
    ]);

test('does not decrement key state if key does not exist', function () {
    $scope = new UserStateScope(['some_key' => 1]);

    $return = $scope->decrement('key');

    expect($return)->toBe($scope)
        ->and($scope->state())->toBe(['some_key' => 1]);
});

test('forget key state', function () {
    $scope = new UserStateScope(['key' => 'value']);

    $return = $scope->forget('key');

    expect($return)->toBe($scope)
        ->and($scope->state())->toBe([]);
});
