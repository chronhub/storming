<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Checkpoint;

use Storm\Projector\Checkpoint\GapType;
use Storm\Projector\Workflow\Notification\Checkpoint\GapDetected;
use Storm\Projector\Workflow\Notification\Checkpoint\RecoverableGapDetected;
use Storm\Projector\Workflow\Notification\Checkpoint\UnrecoverableGapDetected;

it('assert gap detected event', function () {
    $gapType = GapType::IN_GAP;
    expect($gapType->value)->toBe(GapDetected::class);
});

it('assert recoverable gap detected event', function () {
    $gapType = GapType::RECOVERABLE_GAP;
    expect($gapType->value)->toBe(RecoverableGapDetected::class);
});

it('assert unrecoverable gap detected event', function () {
    $gapType = GapType::UNRECOVERABLE_GAP;
    expect($gapType->value)->toBe(UnrecoverableGapDetected::class);
});
