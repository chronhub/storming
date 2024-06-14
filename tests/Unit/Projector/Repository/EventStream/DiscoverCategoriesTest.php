<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Repository\EventStream;

use Storm\Contract\Chronicler\EventStreamProvider;
use Storm\Projector\Exception\InvalidArgumentException;
use Storm\Projector\Repository\EventStream\DiscoverCategories;

beforeEach(function () {
    $this->provider = $this->createMock(EventStreamProvider::class);
});

it('return streams by categories', function () {
    $this->provider->expects($this->once())
        ->method('filterByCategories')
        ->with(['category-1', 'category-2'])
        ->willReturn(['category-1', 'category-2']);

    $query = new DiscoverCategories(['category-1', 'category-2']);
    $streams = $query($this->provider);

    expect($streams)->toBe(['category-1', 'category-2']);
});

it('raise exception if categories is empty', function () {
    new DiscoverCategories([]);
})->throws(InvalidArgumentException::class, 'Categories cannot be empty');

it('raise exception if categories contain duplicate', function () {
    new DiscoverCategories(['category-1', 'category-1']);
})->throws(InvalidArgumentException::class, 'Categories cannot contain duplicate');
