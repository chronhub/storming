<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Repository\EventStream;

use Storm\Contract\Chronicler\EventStreamProvider;
use Storm\Projector\Exception\InvalidArgumentException;
use Storm\Projector\Repository\EventStream\DiscoverCategories;

beforeEach(function () {
    $this->provider = mock(EventStreamProvider::class);
});

test('return streams by categories', function () {
    $this->provider
        ->shouldReceive('filterByCategories')
        ->with(['category-1', 'category-2'])
        ->andReturn(['category-1', 'category-2']);

    $query = new DiscoverCategories(['category-1', 'category-2']);
    $streams = $query($this->provider);

    expect($streams)->toBe(['category-1', 'category-2']);
});

test('raise exception if categories is empty', function () {
    new DiscoverCategories([]);
})->throws(InvalidArgumentException::class, 'Categories cannot be empty');

test('raise exception if categories contain duplicate', function () {
    new DiscoverCategories(['category-1', 'category-1']);
})->throws(InvalidArgumentException::class, 'Categories cannot contain duplicate');
