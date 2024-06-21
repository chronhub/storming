<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow;

use Storm\Projector\Workflow\EmittedStream;

beforeEach(function () {
    $this->emittedStream = new EmittedStream();
});

it('return false when not emitted', function () {
    expect($this->emittedStream->wasEmitted())->toBeFalse();
});

it('return true when emitted', function () {
    $this->emittedStream->emitted();

    expect($this->emittedStream->wasEmitted())->toBeTrue();
});

it('unlink emitted stream', function () {
    $this->emittedStream->emitted();
    expect($this->emittedStream->wasEmitted())->toBeTrue();

    $this->emittedStream->unlink();
    expect($this->emittedStream->wasEmitted())->toBeFalse();
});
