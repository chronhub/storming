<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Stream;

use Storm\Stream\StreamName;

it('create new stream name instance', function () {
    $streamName = new StreamName('stream_name');

    expect($streamName->name)->toBe('stream_name')
        ->and((string) $streamName)->toBe('stream_name');
});

it('raise exception when stream name is empty', function () {
    new StreamName('');
})->throws('InvalidArgumentException', 'Stream name given can not be empty');
