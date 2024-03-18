<?php

declare(strict_types=1);

namespace Storm\Contract\Chronicler;

use Storm\Stream\Stream;
use Storm\Stream\StreamName;

interface Chronicler extends ReadOnlyChronicler
{
    public function append(Stream $stream): void;

    public function delete(StreamName $streamName): void;
}
