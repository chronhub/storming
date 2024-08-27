<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Chronicler;

use Storm\Chronicler\TrackTransactionalStream;
use Storm\Chronicler\TransactionalStreamDraft;

it('create new transactional stream story instance', function (): void {
    $tracker = new TrackTransactionalStream();
    $story = $tracker->newStory('some event');

    expect($story)->toBeInstanceOf(TransactionalStreamDraft::class);
});
