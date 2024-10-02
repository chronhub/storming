<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Subscription;

use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Projection\QueryingProjection;
use Storm\Projector\Projection\QueryProjection;
use Storm\Projector\Workflow\Notification\Command\SprintStopped;

beforeEach(function () {
    $this->hub = mock(NotificationHub::class);
    $this->management = new QueryingProjection($this->hub);
});

test('default instance', function () {
    expect($this->management)->toBeInstanceOf(QueryProjection::class)
        ->and($this->management->hub())->toBe($this->hub);
});

test('stop projection', function () {
    $this->hub->expects('emit')->with(SprintStopped::class);

    $this->management->close();
});
