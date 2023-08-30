<?php

declare(strict_types=1);

namespace Storm\Message;

use Storm\Contract\Message\MessageAlias;

use function class_exists;
use function is_string;

final class AliasFromClassName implements MessageAlias
{
    public function toAlias(string|object $class): string
    {
        if (is_string($class) && ! class_exists($class)) {
            throw InvalidMessageAlias::classDoesNotExists($class);
        }

        return is_string($class) ? $class : $class::class;
    }
}
