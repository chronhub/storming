<?php

declare(strict_types=1);

namespace Storm\Chronicler\Exceptions;

use Storm\Stream\StreamName;
use Throwable;

class StreamNotFound extends RuntimeException
{
    public static function withStreamName(StreamName $streamName, ?Throwable $exception = null): static
    {
        return new static("Stream $streamName not found", 0, $exception);
    }
}
