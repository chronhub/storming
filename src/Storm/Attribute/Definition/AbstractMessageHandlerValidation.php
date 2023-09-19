<?php

declare(strict_types=1);

namespace Storm\Attribute\Definition;

use Storm\Attribute\Exception\DefinitionException;

use function sprintf;

abstract class AbstractMessageHandlerValidation
{
    abstract public function validate(array $map): void;

    protected function createException(string $message, ...$args): DefinitionException
    {
        return new DefinitionException(sprintf($message, ...$args));
    }
}
