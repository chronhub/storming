<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Subscription;

use Storm\Contract\Projector\NotificationHub;
use Storm\Contract\Projector\Repository;
use Storm\Projector\ProjectionStatus;
use Storm\Projector\Provider\InteractWithProvider;
use Storm\Projector\Workflow\Notification\Command\CheckpointReset;
use Storm\Projector\Workflow\Notification\Command\UserStateRestored;

dataset('checkpoints', [[[]], [[1, 5, 20]], [[1000, 5000]]]);
dataset('states', [[[]], [['foo']], [['bar']]]);

beforeEach(function () {
    $this->hub = $hub = mock(NotificationHub::class);
    $this->repository = $repository = mock(Repository::class);
    $this->expectation = new ManagementExpectation($this->repository, $this->hub);

    $this->management = new class($repository, $hub)
    {
        use InteractWithProvider;

        public function __construct(
            protected Repository $projectionRepository,
            protected NotificationHub $hub) {}

        public function mountPersistentProjection(): void
        {
            $this->mountProjection();
        }

        public function resetProjectionSnapshot(): void
        {
            $this->resetSnapshot();
        }
    };
});

test('mount persistent projection', function (bool $isInitialized, ProjectionStatus $currentStatus) {
    $this->expectation->assertMountProjection($isInitialized, $currentStatus);

    $this->management->mountPersistentProjection();
})
    ->with([[true], [false]])
    ->with('projection status');

test('reset projection snapshot', function () {
    $this->hub->expects('emitMany')->with(CheckpointReset::class, UserStateRestored::class);

    $this->management->resetProjectionSnapshot();
});

test('synchronize projection', function () {
    $this->expectation->assertSynchronize();

    $this->management->synchronise();
});

test('disclose projection', function (ProjectionStatus $currentStatus, ProjectionStatus $disclosedStatus) {
    $this->expectation->assertDisclose($currentStatus, $disclosedStatus);

    $this->management->disclose();
})
    ->with('projection status')
    ->with('projection status');

test('close projection and save checkpoint and user state', function (array $checkpoint, array $userState) {
    $this->expectation->assertClose($checkpoint, $userState);

    $this->management->close();
})
    ->with('checkpoints')
    ->with('states');

test('restart projection', function () {
    $this->expectation->assertRestart();

    $this->management->restart();
});

test('freed projection', function () {
    $this->repository->expects('release');

    $this->expectation->assertOnStatusChanged(ProjectionStatus::IDLE);

    $this->management->freed();
});

test('should update lock', function () {
    $this->repository->expects('updateLock');

    $this->management->shouldUpdateLock();
});

test('get projection name', function (string $projectionName) {
    $this->expectation->assertProjectionName($projectionName);

    expect($this->management->getName())->toBe($projectionName);
})->with([['projection', 'proj-1']]);

test('get notification hub', function () {
    expect($this->management->hub())->toBe($this->hub);
});
