<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Stream;

use InvalidArgumentException;
use Storm\Stream\StreamCategoryDetector;
use Storm\Stream\StreamName;

beforeEach(function () {
    $this->detector = new StreamCategoryDetector();
});

test('detect stream category from stream name', function (string $category, string $streamName) {
    $return = $this->detector->detect(new StreamName($streamName));

    expect($return)->toBe($category);
})->with([
    ['category' => 'foo', 'stream_name' => 'foo-bar'],
    ['category' => 'foo', 'stream_name' => 'foo-baz'],
    ['category' => 'foo', 'stream_name' => 'foo-bar-baz'],
]);

test('does not detect stream category from stream name', function (string $streamName) {
    $return = $this->detector->detect(new StreamName($streamName));

    expect($return)->toBeNull();
})->with(['stream_name', 'foo_bar']);

test('raise exception when stream name start with a dash', function () {
    $this->detector->detect(new StreamName('-foo'));
})->throws(InvalidArgumentException::class, 'Stream name -foo cannot start with a category separator');

test('raise exception when category is invalid', function () {
    $this->detector->detect(new StreamName('foo-'));
})
    ->with([
        'start with a dash' => '-foo',
        'end with a dash' => 'foo-',
        'many consecutive dash' => 'foo--bar',
        'many consecutive dash 2' => 'foo-bar--baz',
    ])
    ->throws(InvalidArgumentException::class, 'Invalid stream category');
