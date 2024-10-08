<?php

declare(strict_types=1);

namespace Storm\Projector\Exception;

class ConfigurationViolation extends RuntimeException
{
    public static function withMessage(string $message): self
    {
        return new static($message);
    }
}
