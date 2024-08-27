<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Checkpoint;

use Storm\Projector\Checkpoint\GapRecorder;
use Storm\Stream\StreamPosition;

test('gap range handler', function () {
    $handler = new GapRecorder();

    $gaps = $handler->merge('foo', [], 1, StreamPosition::fromValue(26));
    dump($gaps);

    $again = $handler->merge('foo', $gaps, 26, StreamPosition::fromValue(250));
    dump($again);
    //
    //    $three = $handler->merge('foo', $again, 1000, StreamPosition::fromValue(1500));
    //    dump($three);
    //
    //    $four = $handler->merge('foo', $three, 1599, StreamPosition::fromValue(1601));
    //    dump($four);

});
