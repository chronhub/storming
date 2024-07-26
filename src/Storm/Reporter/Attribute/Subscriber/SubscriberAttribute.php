<?php

declare(strict_types=1);

namespace Storm\Reporter\Attribute\Subscriber;

use Illuminate\Support\Arr;

class SubscriberAttribute
{
    public array $supports;

    public function __construct(
        public string $className,
        public string $event,
        string|array $supports,
        public ?string $method = null,
        public ?int $priority = null,
        public ?string $alias = null,
        public bool $autowire = false,
        public array $references = [],
    ) {
        $this->supports = Arr::wrap($supports);
    }
}
