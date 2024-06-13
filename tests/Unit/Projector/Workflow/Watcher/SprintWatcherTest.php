<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow\Watcher;

use Storm\Projector\Workflow\Watcher\SprintWatcher;

use function method_exists;

beforeEach(function () {
    $this->watcher = new SprintWatcher();
});

it('test new instance', function () {
    expect($this->watcher->inBackground())->toBeFalse()
        ->and($this->watcher->inProgress())->toBeFalse()
        ->and(method_exists($this->watcher, 'subscribe'))->toBeFalse();
});

it('test continue', function () {
    $this->watcher->continue();

    expect($this->watcher->inProgress())->toBeTrue();
});

it('test halt', function () {
    $this->watcher->continue();
    $this->watcher->halt();

    expect($this->watcher->inProgress())->toBeFalse();
});

it('test run in background', function () {
    $this->watcher->runInBackground(true);

    expect($this->watcher->inBackground())->toBeTrue();
});
