<?php

declare(strict_types=1);

namespace Storm\Message;

use function array_key_exists;

trait ReadHeaders
{
    /**
     * @var array<string, null|int|float|string|bool|array|object>
     */
    protected array $headers = [];

    public function header(string $key): int|float|string|bool|array|object|null
    {
        return $this->headers[$key] ?? null;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->headers);
    }

    public function hasNot(string $key): bool
    {
        return ! $this->has($key);
    }

    public function headers(): array
    {
        return $this->headers;
    }
}
