<?php

declare(strict_types=1);

namespace Storm\Aggregate\Attribute;

class AggregateRepositoryAttribute
{
    public function __construct(
        public string $repository,
        public string $abstract,
        public string $chronicler,
        public string $streamName,
        public array $aggregateRoot,
        public string $messageDecorator,
        public string $factory,
        public array $references,
    ) {
    }
}
