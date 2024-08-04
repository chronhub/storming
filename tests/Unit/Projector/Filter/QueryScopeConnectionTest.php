<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Filter;

use Storm\Contract\Projector\DatabaseProjectionQueryFilter;
use Storm\Contract\Projector\LoadLimiterProjectionQueryFilter;
use Storm\Contract\Projector\ProjectionQueryFilter;
use Storm\Projector\Filter\DatabaseQueryScope;
use Storm\Projector\Filter\FromIncludedPosition;
use Storm\Projector\Filter\FromIncludedPositionWithLoadLimiter;

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
