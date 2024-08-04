<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Filter;

use Storm\Contract\Chronicler\InMemoryQueryFilter;
use Storm\Contract\Projector\LoadLimiterProjectionQueryFilter;
use Storm\Contract\Projector\ProjectionQueryFilter;
use Storm\Projector\Filter\InMemoryFromToPosition;
use Storm\Projector\Filter\InMemoryQueryScope;

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
