<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Filter;

use Filter\ProjectionQueryFilter;
use Storm\Contract\Chronicler\InMemoryQueryFilter;
use Storm\Contract\Projector\LoadLimiterProjectionQueryFilter;
use Storm\Projector\Filter\InMemoryQueryScope;
use Storm\Projector\Stream\Filter\InMemoryFromToPosition;

beforeEach(function () {
    $this->queryScope = new InMemoryQueryScope();
});

test('instance from included position', function () {
    $queryFilter = $this->queryScope->fromIncludedPosition();

    expect($queryFilter)->toBeInstanceOf(InMemoryFromToPosition::class)
        ->and($queryFilter)->toBeInstanceOf(InMemoryQueryFilter::class)
        ->and($queryFilter)->toBeInstanceOf(LoadLimiterProjectionQueryFilter::class)
        ->and($queryFilter)->toBeInstanceOf(ProjectionQueryFilter::class);
});
