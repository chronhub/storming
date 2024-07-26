<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Subscription;

use Storm\Contract\Projector\NotificationHub;
use Storm\Contract\Projector\QueryManagement;
use Storm\Projector\Subscription\QueryingManagement;
use Storm\Projector\Workflow\Notification\Command\SprintStopped;

beforeEach(function () {
    $this->hub = mock(NotificationHub::class);
    $this->management = new QueryingManagement($this->hub);
});

test('default instance', function () {
    expect($this->management)->toBeInstanceOf(QueryManagement::class)
        ->and($this->management->hub())->toBe($this->hub);
});

test('stop projection', function () {
    $this->hub->expects('emit')->with(SprintStopped::class);

    $this->management->close();
});
