<?php

declare(strict_types=1);

namespace Storm\Chronicler\Attribute\Subscriber;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class AsStreamSubscriber
{
    public function __construct(
        public string $event,
        public string|array $chronicler,
        public ?string $method = null,
        public bool $autowire = true,
        public int $priority = 0
    ) {
    }
}
