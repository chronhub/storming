<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Watcher;

use Storm\Contract\Clock\SystemClock;
use Storm\Projector\Exception\InvalidArgumentException;

beforeEach(function () {
    $this->clock = $this->createStub(SystemClock::class);
});

describe('raise exception on construction', function () {
    it('when no interval is set', function () {
        new SnapshotWatcher(null, null, null, null);
    })->throws(InvalidArgumentException::class, 'Provide at least one interval between position and time');

    it('when position interval are less than 1', function () {
        new SnapshotWatcher(null, 0, 1, null);
    })->throws(InvalidArgumentException::class, 'Position interval must be greater than 0');

    it('when time interval are less than 1', function () {
        new SnapshotWatcher(null, null, 0, null);
    })->throws(InvalidArgumentException::class, 'Time interval must be greater than 0');

    it('when clock is not set with valid time interval', function () {
        new SnapshotWatcher(null, null, 1, null);
    })->throws(InvalidArgumentException::class, 'Clock must be set when time interval is provided');
});

it('test time interval', function () {

})->todo();

it('test position interval', function () {

})->todo();

it('test position and time interval', function () {

})->todo();
