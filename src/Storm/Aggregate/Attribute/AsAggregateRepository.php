<?php

declare(strict_types=1);

namespace Storm\Aggregate\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class AsAggregateRepository
{
    public function __construct(
        public string $chronicler,
        public string $streamName,
        public string|array $aggregateRoot,
        public string $messageDecorator,
        public ?string $abstract = null,
        public string $factory = AggregateRepositoryFactory::class,
    ) {
    }
}
