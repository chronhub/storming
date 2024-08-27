<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Stream;

use Storm\Stream\StreamName;

dataset('invalid stream name',
    [
        '',
        ' ',
        ' invalid--start',
        'invalid_with_space ',
        '$$invalid--start',
        '$-invalidStart',
        '$_invalidStart',
        '--invalidStart',
        '_invalidStart',
        '__invalidStart',
        '-invalidStart',
        'invalid--string',
        'invalid__string',
        'invalid$string',
        'invalid$$string',
        '$invalid$string',
        '$invalid string',
        'invalidEnd-',
        'invalidEnd_',
        'invalidEnd$',
        'invalid-string_which_contain-many_dashes',
        '*invalid_string',
        'invalid_string*',
        '/invalid_string*',
    ]);

test('default instance', function () {
    $streamName = new StreamName('foo');

    expect($streamName->name)->toBe('foo')
        ->and($streamName->hasPartition())->toBeFalse()
        ->and($streamName->partition())->toBeNull()
        ->and($streamName->isInternal())->toBeFalse()
        ->and((string) $streamName)->toBe('foo')
        ->and($streamName::INTERNAL_PREFIX)->toBe('$')
        ->and($streamName::PARTITION_SEPARATOR)->toBe('-')
        ->and($streamName::PATTERN)->toBe('/^\$?[a-zA-Z0-9]+(?:_[a-zA-Z0-9]+)*(?:-[a-zA-Z0-9]+(?:_[a-zA-Z0-9]+)*)?(?:_[a-zA-Z0-9]+)*$/');
});

test('create new stream name instance', function (string $name) {
    $streamName = new StreamName($name);

    expect($streamName->name)->toBe($name)
        ->and((string) $streamName)->toBe($name);
})->with([
    'validString123',
    'another_valid-string',
    'another_valid_again-string',
    'valid-string',
    'valid_string',
    '$valid_string',
    '$valid_string_foo',
    'valid_string123',
    'valid_string-123',
    'valid_string_123',
    '$valid',
    '$valid_string',
    '$valid-string',
    '$valid_string-123',
    '$valid_string_123',
]);

test('assert', function (string $name) {
    StreamName::assert($name);
})
    ->with('invalid stream name')
    ->throws('InvalidArgumentException', 'Stream name can only contain alphanumeric characters, dollar sign, dashes, and underscores');

// todo separate tests
test('raise exception when stream name is invalid', function (string $name) {
    new StreamName($name);
})
    ->with('invalid stream name')
    ->throws('InvalidArgumentException', 'Stream name can only contain alphanumeric characters, dollar sign, dashes, and underscores');

test('detect partition of stream', function (string $name, ?string $partition) {
    $streamName = new StreamName($name);

    expect($streamName->hasPartition())->toBe((bool) $partition)
        ->and($streamName->partition())->toBe($partition);
})->with([
    ['foo-bar', 'foo'],
    ['foo-bar_baz', 'foo'],
    ['foo_bar-baz', 'foo_bar'],
    ['foo_bar_baz-foo', 'foo_bar_baz'],
    ['foo', null],
    ['foo_bar', null],
    ['foo_bar_baz', null],
    ['foo_bar', null],
]);

test('detect internal stream name', function (string $name, bool $isInternal) {
    $streamName = new StreamName($name);

    expect($streamName->isInternal())->toBe($isInternal);
})->with([
    ['$foo', true],
    ['$foo_bar', true],
    ['$foo-bar', true],
    ['foo', false],
    ['foo_bar', false],
]);
