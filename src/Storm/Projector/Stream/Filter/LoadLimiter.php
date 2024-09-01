<?php

declare(strict_types=1);

namespace Storm\Projector\Stream\Filter;

use Storm\Projector\Exception\InvalidArgumentException;

final readonly class LoadLimiter
{
    /** @var positive-int */
    public int $value;

    /**
     * @throws InvalidArgumentException when value is less than 0
     */
    public function __construct(int $value)
    {
        if ($value < 0) {
            throw new InvalidArgumentException('LoadLimiter value must be greater than or equal to 0');
        }

        if ($value === 0) {
            $value = PHP_INT_MAX;
        }

        $this->value = $value;
    }

    /**
     * Get the maximum position for the given position.
     *
     * @return positive-int
     *
     * @throws InvalidArgumentException when the given position is greater than PHP_INT_MAX
     */
    public function maxPosition(int $position): int
    {
        if ($this->value === PHP_INT_MAX) {
            return PHP_INT_MAX;
        }

        if (PHP_INT_MAX - $this->value < $position) {
            throw new InvalidArgumentException('LoadLimiter value + given position is greater than PHP_INT_MAX');
        }

        return $this->value + $position;
    }
}
