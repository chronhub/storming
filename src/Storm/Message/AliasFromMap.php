<?php

declare(strict_types=1);

namespace Storm\Message;

use Storm\Contract\Message\MessageAlias;

use function class_exists;
use function is_string;

final readonly class AliasFromMap implements MessageAlias
{
    public function __construct(private iterable $map)
    {
    }

    public function toAlias(string|object $class): string
    {
        if (is_string($class) && ! class_exists($class)) {
            throw InvalidMessageAlias::classDoesNotExists($class);
        }

        return $this->determineAlias(is_string($class) ? $class : $class::class);
    }

    private function determineAlias(string $className): string
    {
        if ($alias = $this->map[$className] ?? null) {
            return $alias;
        }

        throw InvalidMessageAlias::unableToLocateInMap($className);
    }
}
