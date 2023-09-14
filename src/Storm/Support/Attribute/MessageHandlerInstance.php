<?php

declare(strict_types=1);

namespace Storm\Support\Attribute;

use Closure;

use function is_callable;

final readonly class MessageHandlerInstance
{
    public function __construct(
        public string $name,
        public object $handler,
        public string $method,
    ) {
    }

    public function call(): callable
    {
        if (is_callable($this->handler)) {
            return $this->handler;
        }

        return Closure::fromCallable([$this->handler, $this->method]);
    }
}
