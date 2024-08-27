<?php

declare(strict_types=1);

namespace Storm\Contract\Message;

/**
 * @template TMessaging of Messaging
 */
interface Messaging
{
    /**
     * Returns a new instance of Messaging with the given content.
     *
     * @param array<string, int|float|string|bool|array|object|null> $content
     */
    public static function fromContent(array $content): static;

    /**
     * Returns the content of the message.
     *
     * @return array<string, int|float|string|bool|array|object|null>
     */
    public function toContent(): array;

    /**
     * Returns a new instance of Messaging with the given headers.
     *
     * @param array<string, int|float|string|bool|array|object|null> $headers
     */
    public function withHeaders(array $headers): static;

    /**
     * Returns a new instance of Messaging with the given header.
     */
    public function withHeader(string $header, int|float|string|bool|array|object|null $value): static;

    /**
     * Checks if the message has the given header.
     */
    public function has(string $key): bool;

    /**
     * Checks if the message does not have the given header.
     */
    public function hasNot(string $key): bool;

    /**
     * Returns the value of the given header.
     */
    public function header(string $key): int|float|string|bool|array|object|null;

    /**
     * Returns all headers of the message.
     *
     * @return array<string, int|float|string|bool|array|object|null>
     */
    public function headers(): array;
}
