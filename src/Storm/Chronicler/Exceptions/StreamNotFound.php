<?php

declare(strict_types=1);

namespace Storm\Chronicler\Exceptions;

use Storm\Stream\StreamName;

class StreamNotFound extends RuntimeException
{
    public static function withStreamName(StreamName $streamName): static
    {
        return new static("Stream $streamName not found");
    }
}
