<?php

declare(strict_types=1);

namespace Storm\Message;

use Storm\Contract\Message\MessageAlias;
use function basename;
use function class_exists;
use function ctype_lower;
use function is_string;
use function mb_strtolower;
use function preg_replace;
use function str_replace;
use function ucwords;

final class AliasFromInflector implements MessageAlias
{
    public function toAlias(string|object $class): string
    {
        if (is_string($class) && ! class_exists($class)) {
            throw InvalidMessageAlias::classDoesNotExists($class);
        }

        return $this->produceAlias(is_string($class) ? $class : $class::class);
    }

    private function produceAlias(string $className): string
    {
        $delimiter = '-';

        $alias = basename(str_replace('\\', '/', $className));

        if (! ctype_lower($className)) {
            $alias = preg_replace('/\s+/u', '', ucwords($alias));

            $alias = mb_strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1'.$delimiter, $alias));
        }

        return $alias;
    }
}
