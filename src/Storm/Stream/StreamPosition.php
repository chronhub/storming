<?php

declare(strict_types=1);

namespace Storm\Stream;

use InvalidArgumentException;

use function gettype;
use function is_int;

final readonly class StreamPosition
{
    /**
     * @param positive-int $value
     */
    public function __construct(public int $value)
    {
        /** @phpstan-ignore-next-line */
        if ($this->value < 1) {
            throw new InvalidArgumentException("Invalid stream position: must be greater than 0, current value is $this->value");
        }
    }

    /**
     * Create a new stream position from a value.
     *
     * @throws InvalidArgumentException When the value is not an integer.
     * @throws InvalidArgumentException When the value is less than 1.
     */
    public static function fromValue(mixed $value): self
    {
        if (! is_int($value)) {
            throw new InvalidArgumentException('Invalid stream position: must be an integer, current value type is: '.gettype($value));
        }

        return new self($value);
    }

    /**
     * Check if the stream position is equal to the given position.
     *
     * @param positive-int $position
     */
    public function equalsTo(int $position): bool
    {
        return $this->value === $position;
    }

    /**
     * Check if the stream position is greater than the given position.
     *
     * @param positive-int $position
     */
    public function isGreaterThan(int $position): bool
    {
        return $this->value > $position;
    }

    /**
     * Check if the stream position is greater than or equal to the given position.
     *
     * @param positive-int $position
     */
    public function isGreaterThanOrEqual(int $position): bool
    {
        return $this->value >= $position;
    }

    /**
     * Check if the stream position is less than the given position.
     *
     * @param positive-int $position
     */
    public function isLessThan(int $position): bool
    {
        return $this->value < $position;
    }

    /**
     * Check if the stream position is less than or equal to the given position.
     *
     * @param positive-int $position
     */
    public function isLessThanOrEqual(int $position): bool
    {
        return $this->value <= $position;
    }

    /**
     * Check if the stream position is between the given inclusive positions.
     *
     * @param positive-int $from "from" position (inclusive) must be less than "to" position.
     * @param positive-int $to   "to" position (inclusive) must be greater than "from" position.
     *
     * @throws InvalidArgumentException When from position is greater than to position.
     */
    public function isBetween(int $from, int $to): bool
    {
        if ($from > $to) {
            throw new InvalidArgumentException('Invalid positions given: from position must be less than to position');
        }

        return $this->value >= $from && $this->value <= $to;
    }
}
