<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Subscription;

use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Provider\QueryingProvider;
use Storm\Projector\Provider\QueryProvider;
use Storm\Projector\Workflow\Notification\Command\SprintStopped;

beforeEach(function () {
    $this->hub = mock(NotificationHub::class);
    $this->management = new QueryingProvider($this->hub);
});

test('default instance', function () {
    expect($this->management)->toBeInstanceOf(QueryProvider::class)
        ->and($this->management->hub())->toBe($this->hub);
});

test('stop projection', function () {
    $this->hub->expects('emit')->with(SprintStopped::class);

    $this->management->close();
});
