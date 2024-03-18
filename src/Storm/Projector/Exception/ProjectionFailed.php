<?php

declare(strict_types=1);

namespace Storm\Projector\Exception;

use Throwable;

class ProjectionFailed extends RuntimeException
{
    public static function from(Throwable $exception, ?string $message = null): self
    {
        return new static(
            $message ?? $exception->getMessage(),
            $exception->getCode(),
            $exception
        );
    }

    public static function failedOnUpdateStatus(string $streamName, ProjectionStatus $status, Throwable $exception): self
    {
        $message = "Unable to update projection status for stream name $streamName and status $status->value";

        return static::from($exception, $message);
    }
}
