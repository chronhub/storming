<?php

declare(strict_types=1);

namespace Storm\Serializer;

use JsonSerializable;

final readonly class Payload implements JsonSerializable
{
    //todo content and header a string (json) should be used for EventPayload only
    /**
     * @param non-empty-string|array<string|array>                $content
     * @param non-empty-string|array<string|array<string, mixed>> $headers
     * @param null|int<1,max>                                     $seqNo
     */
    public function __construct(
        public string|array $content,
        public string|array $headers,
        public ?int $seqNo = null)
    {
    }

    public function jsonSerialize(): array
    {
        return [
            'headers' => $this->headers,
            'content' => $this->content,
            'seqNo' => $this->seqNo,
        ];
    }
}
