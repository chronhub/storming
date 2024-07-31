<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow\Watcher;

use Storm\Projector\Workflow\Component\Runner;

use function method_exists;

beforeEach(function () {
    $this->watcher = new Runner();
});

test('default instance', function () {
    expect($this->watcher->inBackground())->toBeFalse()
        ->and($this->watcher->inProgress())->toBeFalse()
        ->and(method_exists($this->watcher, 'subscribe'))->toBeFalse();
});

test('continue sprint', function () {
    $this->watcher->continue();

    expect($this->watcher->inProgress())->toBeTrue();
});

test('halt sprint', function () {
    $this->watcher->continue();
    $this->watcher->halt();

    expect($this->watcher->inProgress())->toBeFalse();
});

test('run in background', function (bool $inBackground) {
    $this->watcher->runInBackground($inBackground);

    expect($this->watcher->inBackground())->toBe($inBackground);
})->with('keep projection running');
