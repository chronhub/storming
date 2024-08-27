<?php

declare(strict_types=1);

namespace Storm\Stream;

use InvalidArgumentException;
use Stringable;

use function explode;
use function preg_match;
use function str_contains;

final readonly class StreamName implements Stringable
{
    final public const string INTERNAL_PREFIX = '$';

    final public const string PARTITION_SEPARATOR = '-';

    /**
     * Regex rules:
     *
     * No spaces allowed in the entire string.
     * String can start with either:
     *      a) A single dollar sign ($)
     *      b) Alphanumeric characters
     * Dash (-) allowed only once
     * Underscores (_) allowed and can be repeated but not consecutive
     * Both dash (-) and underscore (_) must be followed by alphanumeric characters
     */
    final public const string PATTERN = '/^\$?[a-zA-Z0-9]+(?:_[a-zA-Z0-9]+)*(?:-[a-zA-Z0-9]+(?:_[a-zA-Z0-9]+)*)?(?:_[a-zA-Z0-9]+)*$/';

    public function __construct(public string $name)
    {
        self::assert($name);
    }

    /**
     * Check if the stream name is internal.
     */
    public function isInternal(): bool
    {
        return $this->name[0] === self::INTERNAL_PREFIX;
    }

    /**
     * Get the partition name from the stream name if it exists.
     */
    public function partition(): ?string
    {
        return $this->hasPartition()
            ? explode(self::PARTITION_SEPARATOR, $this->name)[0]
            : null;
    }

    /**
     * Check if the stream name has a partition.
     */
    public function hasPartition(): bool
    {
        return str_contains($this->name, self::PARTITION_SEPARATOR);
    }

    /**
     * Assert that the given name is valid.
     *
     * @throws InvalidArgumentException When the name is invalid
     */
    public static function assert(string $name): void
    {
        if (! preg_match(self::PATTERN, $name)) {
            throw new InvalidArgumentException('Stream name can only contain alphanumeric characters, dollar sign, dashes, and underscores');
        }
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
