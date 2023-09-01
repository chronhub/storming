<?php

declare(strict_types=1);

namespace Storm\Serializer;

use JsonSerializable;

final readonly class Payload implements JsonSerializable
{
    //todo content and header a string (json) should be used for EventPayload only
    public function __construct(
        public string|array $content,
        public string|array $headers,
        public ?int $seqNo = null)
    {
    }

    /**
     * @return array{headers:string|array, content:string|array, seqNo:positive-int|null}
     */
    public function jsonSerialize(): array
    {
        return [
            'headers' => $this->headers,
            'content' => $this->content,
            'seqNo' => $this->seqNo,
        ];
    }
}
