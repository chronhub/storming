<?php

declare(strict_types=1);

namespace Storm\Attribute\Definition;

use InvalidArgumentException;
use Storm\Attribute\Definition;

use function class_exists;

class SubscriberDefinition extends Definition
{
    public function __construct(
        protected string $className,
        protected string $eventName,
        protected int $priority,
    ) {
        if (! class_exists($this->className)) {
            throw new InvalidArgumentException("Class $this->className does not exist");
        }
    }
}
