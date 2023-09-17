<?php

declare(strict_types=1);

namespace Storm\Attribute;

abstract class Definition
{
    /**
     * @var array<empty>|array<non-empty-string, array<empty|class-string>>
     */
    protected array $calls = [];

    public function addMethod(string $methodName, array $parameters = []): void
    {
        $this->calls[] = [$methodName, $parameters];
    }
}
