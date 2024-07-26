<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Stream;

use InvalidArgumentException;
use Storm\Stream\StreamPosition;
use Storm\Tests\Stubs\Double\Message\SomeEvent;

use function gettype;

dataset('invalid types', [
    'string' => 'string',
    'float' => 1.1,
    'array' => [[]],
    'null' => fn () => null,
    'false' => false,
    'true' => true,
    'object' => new class {},
]);

dataset('valid integer', [1, 10, 1000]);
dataset('invalid integer', [0, -1, -10, -1000]);

test('default instance', function (int $value) {
    $streamPosition = new StreamPosition($value);

    expect($streamPosition->value)->toBe($value);
})->with('valid integer');

test('invalid instance', function (int $value) {
    new StreamPosition($value);
})->with('invalid integer')
    ->throws(InvalidArgumentException::class, 'Invalid stream position: must be greater than 0, current value is');

test('from value', function (int $value) {
    $event = SomeEvent::fromContent([])->withHeader('position', $value);

    $streamPosition = StreamPosition::fromValue($event->header('position'));

    expect($streamPosition->value)->toBe($value);
})->with('valid integer');

test('from value with invalid position', function (mixed $position) {
    $event = SomeEvent::fromContent([])->withHeader('position', $position);

    try {
        StreamPosition::fromValue($event->header('position'));
    } catch (InvalidArgumentException $exception) {
        expect($exception->getMessage())->toBe('Invalid stream position: must be an integer, current value type is: '.gettype($position));
    }
})->with('invalid types');

test('equals to', function (int $position) {
    $streamPosition = new StreamPosition($position);

    expect($streamPosition->equalsTo($position))->toBeTrue()
        ->and($streamPosition->equalsTo($position + 1))->toBeFalse();
})->with('valid integer');

test('is greater than', function (int $position) {
    $streamPosition = new StreamPosition($position);

    expect($streamPosition->isGreaterThan($position - 1))->toBeTrue()
        ->and($streamPosition->isGreaterThan($position + 1))->toBeFalse();
})->with('valid integer');

test('is greater than or equal', function (int $position) {
    $streamPosition = new StreamPosition($position);

    expect($streamPosition->isGreaterThanOrEqual($position))->toBeTrue()
        ->and($streamPosition->isGreaterThanOrEqual($position - 1))->toBeTrue()
        ->and($streamPosition->isGreaterThanOrEqual($position + 1))->toBeFalse();
})->with('valid integer');

test('is between', function (int $position) {
    $streamPosition = new StreamPosition($position);

    expect($streamPosition->isBetween($position, $position + 1))->toBeTrue()
        ->and($streamPosition->isBetween($position - 1, $position + 1))->toBeTrue()
        ->and($streamPosition->isBetween($position - 1, $position))->toBeTrue()
        ->and($streamPosition->isBetween($position + 1, $position + 2))->toBeFalse();
})->with('valid integer');

test('is between raises exception when from position is greater than to position', function () {
    $streamPosition = new StreamPosition(1);

    expect($streamPosition->isBetween(2, 1))->toBeFalse();
})->throws(InvalidArgumentException::class, 'Invalid positions given: from position must be less than to position');
