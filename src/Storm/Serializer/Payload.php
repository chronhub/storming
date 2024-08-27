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
        /** @var T */ public string|array $header,
        /** @var T */ public string|array $content,
        /** @var S */ public ?int $position = null
    ) {}

    /**
     * @return array{header:T, content:T, position:S}
     */
    public function jsonSerialize(): array
    {
        return [
            'header' => $this->header,
            'content' => $this->content,
            'position' => $this->position,
        ];
    }
}
