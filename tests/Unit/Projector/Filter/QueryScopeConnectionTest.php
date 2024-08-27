<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Filter;

use Filter\DatabaseProjectionQueryFilter;
use Filter\ProjectionQueryFilter;
use Storm\Contract\Projector\LoadLimiterProjectionQueryFilter;
use Storm\Projector\Filter\DatabaseQueryScope;
use Storm\Projector\Filter\FromIncludedPositionWithLoadLimiter;
use Storm\Projector\Stream\Filter\FromIncludedPosition;

beforeEach(function () {
    $this->queryScopeConnection = new DatabaseQueryScope();
});

test('from included position', function () {
    $queryFilter = $this->queryScopeConnection->fromIncludedPosition();

    expect($queryFilter)->toBeInstanceOf(FromIncludedPosition::class)
        ->and($queryFilter)->toBeInstanceOf(DatabaseProjectionQueryFilter::class)
        ->and($queryFilter)->toBeInstanceOf(ProjectionQueryFilter::class);
});

test('from included position with load limiter', function () {
    $queryFilter = $this->queryScopeConnection->fromIncludedPositionWithLoadLimiter();

    expect($queryFilter)->toBeInstanceOf(FromIncludedPositionWithLoadLimiter::class)
        ->and($queryFilter)->toBeInstanceOf(DatabaseProjectionQueryFilter::class)
        ->and($queryFilter)->toBeInstanceOf(ProjectionQueryFilter::class)
        ->and($queryFilter)->toBeInstanceOf(LoadLimiterProjectionQueryFilter::class);
});
