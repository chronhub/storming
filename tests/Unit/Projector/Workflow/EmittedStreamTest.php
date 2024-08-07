<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow;

use Storm\Projector\Stream\EmittedStream;

beforeEach(function () {
    $this->emittedStream = new EmittedStream();
});

test('default instance', function () {
    expect($this->emittedStream->wasEmitted())->toBeFalse();
});

test('return true when emitted', function () {
    expect($this->emittedStream->wasEmitted())->toBeFalse();

    $this->emittedStream->emitted();

    expect($this->emittedStream->wasEmitted())->toBeTrue();
});

test('unlink emitted stream', function () {
    expect($this->emittedStream->wasEmitted())->toBeFalse();

    $this->emittedStream->emitted();
    expect($this->emittedStream->wasEmitted())->toBeTrue();

    $this->emittedStream->unlink();
    expect($this->emittedStream->wasEmitted())->toBeFalse();
});
