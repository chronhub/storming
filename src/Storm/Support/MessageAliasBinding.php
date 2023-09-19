<?php

declare(strict_types=1);

namespace Storm\Support;

use InvalidArgumentException;

use function class_exists;
use function mb_strtolower;
use function str_replace;

class MessageAliasBinding
{
    public static function fromMessageName(string $messageName): string
    {
        if (! class_exists($messageName)) {
            throw new InvalidArgumentException("Message class $messageName does not exist.");
        }

        return str_replace('\\', '-', mb_strtolower($messageName));
    }
}
