<?php

declare(strict_types=1);

namespace Storm\Chronicler\Exceptions;

use Storm\Stream\StreamName;

class StreamAlreadyExists extends RuntimeException
{
    public static function withStreamName(StreamName $streamName): self
    {
        return new self("Stream $streamName already exists");
    }
}
