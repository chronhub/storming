<?php

declare(strict_types=1);

namespace Storm\Projector\Repository;

use function is_array;

final class ProjectionFactory
{
    public static function make(array|object $data): Projection
    {
        if (is_array($data)) {
            return self::fromArray($data);
        }

        return self::fromObject($data);
    }

    public static function fromObject(object $data): Projection
    {
        return new Projection(
            name: $data->name,
            status: $data->status,
            state: $data->state,
            checkpoint: $data->checkpoint,
            lockedUntil: $data->locked_until,
        );
    }

    public static function fromArray(array $data): Projection
    {
        return new Projection(
            name: $data['name'],
            status: $data['status'],
            state: $data['state'],
            checkpoint: $data['checkpoint'],
            lockedUntil: $data['locked_until'],
        );
    }
}
