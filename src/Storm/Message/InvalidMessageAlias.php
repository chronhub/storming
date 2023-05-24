<?php

declare(strict_types=1);

namespace Storm\Message;

use InvalidArgumentException;

class InvalidMessageAlias extends InvalidArgumentException
{
    public static function classDoesNotExists(string $className): self
    {
        return new static("Message class name $className does not exists.");
    }

    public static function unableToLocateInMap(string $className): self
    {
        return new static("Message class name $className not found in map.");
    }
}
