<?php

declare(strict_types=1);

namespace Storm\Message;

use Storm\Contract\Message\Messaging;

/**
 * @phpstan-require-implements Messaging
 */
trait WriteHeaders
{
    use ReadHeaders;

    public function withHeader(string $header, int|float|string|bool|array|object|null $value): static
    {
        $instance = clone $this;

        $instance->headers[$header] = $value;

        return $instance;
    }

    public function withHeaders(array $headers): static
    {
        $instance = clone $this;

        $instance->headers = $headers;

        return $instance;
    }
}
