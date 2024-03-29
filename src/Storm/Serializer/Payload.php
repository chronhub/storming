<?php

declare(strict_types=1);

namespace Storm\Serializer;

use JsonSerializable;

/**
 * @template T as non-empty-string|array<string, mixed>
 * @template S as positive-int|null
 */
final readonly class Payload implements JsonSerializable
{
    public function __construct(
        /** @var T */ public string|array $headers,
        /** @var T */ public string|array $content,
        /** @var S */ public ?int $seqNo = null
    ) {
    }

    /**
     * @return array{headers:T, content:T, seqNo:S}
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
