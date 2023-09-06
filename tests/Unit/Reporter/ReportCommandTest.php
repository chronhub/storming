<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Reporter;

use Storm\Tests\Unit\Reporter\Stub\ReportCommandStub;
use Storm\Tracker\TrackMessage;

it('test default instance', function () {
    $reportCommand = new ReportCommandStub();

    expect($reportCommand->tracker)->toBeInstanceOf(TrackMessage::class)
        ->and($reportCommand->tracker()->listeners())->toHaveCount(0);
});

it('test instance with given tracker', function () {
    $tracker = new TrackMessage();
    $reportCommand = new ReportCommandStub($tracker);

    expect($reportCommand->tracker)->toBe($tracker)
        ->and($reportCommand->tracker()->listeners())->toHaveCount(0);
});
