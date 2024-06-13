<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow\Watcher;

use Storm\Projector\Workflow\Watcher\AckedStreamWatcher;

use function method_exists;

beforeEach(function () {
    $this->watcher = new AckedStreamWatcher();
});

function assertAckedStreamEmpty($watcher, string ...$streamName): void
{
    expect($watcher->hasStreams())->toBeFalse()
        ->and($watcher->streams())->not()->toContain($streamName)
        ->and($watcher->streams())->toBeEmpty();
}

it('test new instance', function () {
    assertAckedStreamEmpty($this->watcher, 'stream-1');

    expect(method_exists($this->watcher, 'subscribe'))->toBeFalse();
});

it('ack stream', function () {
    $this->watcher->ack('stream-1');

    expect($this->watcher->hasStreams())->toBeTrue()
        ->and($this->watcher->streams())->toBe(['stream-1']);
});

it('ack multiple streams', function () {
    $this->watcher->ack('stream-1');
    $this->watcher->ack('stream-2');

    expect($this->watcher->hasStreams())->toBeTrue()
        ->and($this->watcher->streams())->toBe(['stream-1', 'stream-2']);
});

it('does not duplicate acked streams', function () {
    $this->watcher->ack('stream-1');
    $this->watcher->ack('stream-1');

    expect($this->watcher->hasStreams())->toBeTrue()
        ->and($this->watcher->streams())->toBe(['stream-1']);
});

it('reset streams', function () {
    $this->watcher->ack('stream-1');
    $this->watcher->ack('stream-2');

    expect($this->watcher->hasStreams())->toBeTrue()
        ->and($this->watcher->streams())->toBe(['stream-1', 'stream-2']);

    $this->watcher->reset();

    assertAckedStreamEmpty($this->watcher, 'stream-1', 'stream-2');
});
