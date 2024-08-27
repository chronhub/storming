<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Checkpoint;

use Storm\Projector\Checkpoint\GapType;
use Storm\Projector\Workflow\Notification\GapDetected;
use Storm\Projector\Workflow\Notification\RecoverableGapDetected;
use Storm\Projector\Workflow\Notification\UnrecoverableGapDetected;

test('assert gap detected event', function () {
    $gapType = GapType::IN_GAP;
    expect($gapType->value)->toBe(GapDetected::class);
});

test('assert recoverable gap detected event', function () {
    $gapType = GapType::RECOVERABLE_GAP;
    expect($gapType->value)->toBe(RecoverableGapDetected::class);
});

test('assert unrecoverable gap detected event', function () {
    $gapType = GapType::UNRECOVERABLE_GAP;
    expect($gapType->value)->toBe(UnrecoverableGapDetected::class);
});
