<?php

declare(strict_types=1);

namespace Storm\Attribute\Definition;

final readonly class MessageHandlerMethod
{
    public function __construct(
        public string $className,
        public string $methodName,
        public MessageDeclarationScope $flag,
    ) {
    }
}
