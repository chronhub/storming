<?php

declare(strict_types=1);

namespace Storm\Stream;

use Stringable;
use function assert;

final class StreamName implements Stringable
{
    public function __construct(public string $name)
    {
        assert($this->name !== '', 'Stream name given can not be empty');
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
