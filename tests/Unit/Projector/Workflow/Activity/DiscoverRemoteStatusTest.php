<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow\Activity;

use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\ProjectionStatus;
use Storm\Projector\Workflow\Activity\DiscoverRemoteStatus;

dataset('selected status', [
    'stopping' => fn () => ProjectionStatus::STOPPING,
    'deleting' => fn () => ProjectionStatus::DELETING,
    'deleting with emitted events' => fn () => ProjectionStatus::DELETING_WITH_EMITTED_EVENTS,
    'running' => fn () => ProjectionStatus::RUNNING,
    'idle' => fn () => ProjectionStatus::IDLE,
]);

beforeEach(function () {
    $this->hub = mock(NotificationHub::class);
    $this->monitor = new DiscoverRemoteStatus();
    $this->stub = new MonitorRemoteStatusExpectation();
});

test('should stop depends on disclosed status', function (ProjectionStatus $status) {
    $this->stub->discoverRemoteStatus($this->hub, $status);

    switch ($status) {
        case ProjectionStatus::STOPPING:
            $this->stub->expectsStopping($this->hub, $this->monitor);

            break;

        case ProjectionStatus::DELETING:
            $this->stub->expectsDeleting($this->hub, $this->monitor, false);

            break;

        case ProjectionStatus::DELETING_WITH_EMITTED_EVENTS:
            $this->stub->expectsDeleting($this->hub, $this->monitor, true);

            break;

        default:
            $result = $this->monitor->handle($this->hub);

            expect($result)->toBeFalse();
    }
})->with('selected status');

test('never stop projection on discovering resetting status', function (bool $keepRunning) {
    $this->stub->discoverRemoteStatus($this->hub, ProjectionStatus::RESETTING);
    $this->stub->expectsResetting($this->hub, $this->monitor, $keepRunning);
})->with('keep projection running');
