<?php

declare(strict_types=1);

namespace Storm\Message;

use Storm\Contract\Message\Messaging;

/**
 * @phpstan-require-implements Messaging
 */
trait HasConstructableContent
{
    public function __construct(public readonly array $content) {}

    public function toContent(): array
    {
        return $this->content;
    }

    public static function fromContent(array $content): static
    {
        return new static($content);
    }
}
