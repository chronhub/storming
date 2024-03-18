<?php

declare(strict_types=1);

namespace Storm\Message\Attribute;

use JsonSerializable;

class MessageAttribute implements JsonSerializable
{
    public function __construct(
        public string $reporterId,
        public string $handlerClass,
        public string $handlerMethod,
        public string $handles,
        public null|string|array $queue,
        public int $priority,
        public string $type,
        public array $references,
        public ?string $handlerId = null,
        public ?string $messageId = null,
    ) {
    }

    /**
     * @return array{
     *     reporter_id: string,
     *     handler_class: string,
     *     handler_method: string,
     *     handles: string,
     *     queue: null|string|array,
     *     priority: int,
     *     type: string,
     *     references: array,
     *     handler_id: null|string,
     *     message_id: null|string,
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'reporter_id' => $this->reporterId,
            'handler_class' => $this->handlerClass,
            'handler_method' => $this->handlerMethod,
            'handles' => $this->handles,
            'queue' => $this->queue,
            'priority' => $this->priority,
            'type' => $this->type,
            'references' => $this->references,
            'handler_id' => $this->handlerId,
            'message_id' => $this->messageId,
        ];
    }

    public function newInstance(string $handlerId, string $messageId, ?array $queue): self
    {
        $self = clone $this;
        $self->handlerId = $handlerId;
        $self->messageId = $messageId;
        $self->queue = $queue;

        return $self;
    }
}
