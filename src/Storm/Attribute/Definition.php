<?php

declare(strict_types=1);

namespace Storm\Attribute;

use JsonSerializable;

abstract class Definition implements JsonSerializable
{
    /**
     * @var array<empty>|array<non-empty-string, array<empty|non-empty-string>>
     */
    protected array $references = [];

    public function addMethod(string $methodName, array $parameters = []): void
    {
        if ($methodName === '__invoke' && $parameters === []) {
            return;
        }

        $this->references[] = [$methodName => $parameters];
    }
}
