<?php

namespace Storm\Reporter\Producer;

use JsonSerializable;

class QueueOption implements JsonSerializable
{
    public function __construct(
        public ?string $connection = null,
        public ?string $name = null,
        public ?int $tries = null,
        public ?int $maxExceptions = null,
        public null|int|string $delay = null,
        public ?int $timeout = null,
        public ?int $backoff = null,
    ) {
    }

    /**
     * @return array{
     *     connection: ?string,
     *     name: ?string,
     *     tries: ?int,
     *     max_exceptions: ?int,
     *     delay: null|int|string,
     *     timeout: ?int,
     *     backoff: ?int
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'connection' => $this->connection,
            'name' => $this->name,
            'tries' => $this->tries,
            'max_exceptions' => $this->maxExceptions,
            'delay' => $this->delay,
            'timeout' => $this->timeout,
            'backoff' => $this->backoff,
        ];
    }
}