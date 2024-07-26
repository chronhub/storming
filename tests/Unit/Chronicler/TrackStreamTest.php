<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Chronicler;

use Storm\Chronicler\StreamDraft;
use Storm\Chronicler\TrackStream;

it('create new stream story instance', function (): void {
    $tracker = new TrackStream();
    $story = $tracker->newStory('some event');

    expect($story)->toBeInstanceOf(StreamDraft::class)
        ->and($story->currentEvent())->toBe('some event');
});

it('override current event', function (): void {
    $tracker = new TrackStream();
    $story = $tracker->newStory('some event');

    expect($story->currentEvent())->toBe('some event');

    $story->withEvent('new event');

    expect($story->currentEvent())->toBe('new event');
});
