<?php

declare(strict_types=1);

namespace Storm\Chronicler;

final readonly class EventStream
{
    public function __construct(
        private string $streamName,
        private string $tableName,
        private ?string $partition = null
    ) {
    }

    public function realStreamName(): string
    {
        return $this->streamName;
    }

    public function tableName(): string
    {
        return $this->tableName;
    }

    public function partition(): ?string
    {
        return $this->partition;
    }

    public function jsonSerialize(): array
    {
        return [
            'real_stream_name' => $this->streamName,
            'stream_name' => $this->tableName,
            'partition' => $this->partition,
        ];
    }
}
