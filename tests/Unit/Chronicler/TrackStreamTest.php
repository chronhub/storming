<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Chronicler;

use Storm\Chronicler\StreamDraft;
use Storm\Chronicler\TrackStream;

it('create new stream story instance', function (): void {
    $tracker = new TrackStream();
    $story = $tracker->newStory('some event');

    expect($story)->toBeInstanceOf(StreamDraft::class);
});
