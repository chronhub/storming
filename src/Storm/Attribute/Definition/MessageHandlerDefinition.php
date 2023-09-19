<?php

declare(strict_types=1);

namespace Storm\Attribute\Definition;

use InvalidArgumentException;
use Storm\Attribute\Definition;

use function class_exists;

final class MessageHandlerDefinition extends Definition
{
    public function __construct(
        public readonly string $className,
        public readonly string $messageName,
        public readonly string $methodName,
        public readonly int $priority,
        public readonly MessageDeclarationScope $scope,
    ) {
        if (! class_exists($this->className)) {
            throw new InvalidArgumentException("Class $this->className does not exist");
        }

        if (! class_exists($this->messageName)) {
            throw new InvalidArgumentException("Message name $this->messageName does not exist");
        }
    }

    public function addMethod(string $methodName, array $parameters = []): void
    {
        if ($methodName !== '__construct') {
            throw new InvalidArgumentException('Only __construct method is allowed for message handlers');
        }

        parent::addMethod($methodName, $parameters);
    }

    /**
     * @return array{class: string, method: string, priority: int, scope: value-of<MessageDeclarationScope::*>, references: array}
     */
    public function jsonSerialize(): array
    {
        return [
            'class' => $this->className,
            'method' => $this->methodName,
            'priority' => $this->priority,
            'scope' => $this->scope->value,
            'references' => $this->references,
        ];
    }
}
