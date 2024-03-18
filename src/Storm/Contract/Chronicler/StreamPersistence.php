<?php

declare(strict_types=1);

namespace Storm\Contract\Chronicler;

use Storm\Stream\Stream;

interface StreamPersistence
{
    public function serialize(Stream $stream): array;
}
