<?php

declare(strict_types=1);

namespace Storm\Attribute\Definition;

use InvalidArgumentException;
use Storm\Attribute\Definition;

use function class_exists;

final class ReporterDefinition extends Definition
{
    public function __construct(
        public readonly string $className,
        public readonly string $alias,
        public readonly string $filter,
        public readonly string $tracker,
    ) {
        if (! class_exists($this->className)) {
            throw new InvalidArgumentException("Class $this->className does not exist");
        }
    }

    /**
     * @return array{class: string, alias: string, filter: string, tracker: string, references: array}
     */
    public function jsonSerialize(): array
    {
        return [
            'class' => $this->className,
            'alias' => $this->alias,
            'filter' => $this->filter,
            'tracker' => $this->tracker,
            'references' => $this->references,
        ];
    }
}
