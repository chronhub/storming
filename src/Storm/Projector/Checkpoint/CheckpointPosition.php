<?php

declare(strict_types=1);

namespace Storm\Projector\Checkpoint;

use Storm\Projector\Exception\InvalidArgumentException;

final readonly class CheckpointPosition
{
    private function __construct(public int $value)
    {
        if ($this->value < 0) {
            throw new InvalidArgumentException('Checkpoint position must be greater or equals than zero');
        }
    }

    public static function fromInteger(int $value): self
    {
        return new self($value);
    }

    public function toInteger(): int
    {
        return $this->value;
    }

    /**
     * @throws InvalidArgumentException when the position is less than zero
     */
    public function assertPositive(): void
    {
        if ($this->value <= 0) {
            throw new InvalidArgumentException('Checkpoint position must be positive integer');
        }
    }
}
