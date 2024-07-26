<?php

declare(strict_types=1);

namespace Storm\Chronicler\Attribute\Subscriber;

class StreamSubscriberHandler
{
    /**
     * @var callable
     */
    public $instance;

    public function __construct(
        public string $event,
        callable $instance,
        public int $priority,
    ) {
        $this->instance = $instance;
    }
}
