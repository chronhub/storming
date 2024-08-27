<?php

declare(strict_types=1);

namespace Storm\Story\Build;

use JsonSerializable;

readonly class MessageHandlerMetadata implements JsonSerializable
{
    public function __construct(
        public string $type,
        public string $handler,
        public string $method,
        public string|array $queue,
        public int $priority,
        public string|array $middleware = [],
        public ?string $contextId = null,
    ) {}

    /**
     * @return array{
     *     type: string,
     *     handler: class-string,
     *     method: string,
     *     queue: string|array,
     *     priority: int,
     *     middleware: string|array,
     *     contextId: string|null,
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'type' => $this->type,
            'handler' => $this->handler,
            'method' => $this->method,
            'queue' => $this->queue,
            'priority' => $this->priority,
            'middleware' => $this->middleware,
            'contextId' => $this->contextId,
        ];
    }
}
