<?php

declare(strict_types=1);

namespace Storm\Projector\Repository;

use function is_array;

/**
 * @phpstan-type Data array{
 *     name: string,
 *     status: string,
 *     checkpoint?: string|null,
 *     state?: string|null,
 *     locked_until?: string|null,
 * }
 */
final class ProjectionFactory
{
    public static function create(string $name, string $status): Projection
    {
        return new Projection($name, $status, null, null, null);
    }

    /**
     * @param Data|object $data
     */
    public static function make(array|object $data): Projection
    {
        return is_array($data) ? self::fromArray($data) : self::fromObject($data);
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

    /**
     * @param Data $data
     */
    public static function fromArray(array $data): Projection
    {
        return new Projection(
            name: $data['name'],
            status: $data['status'],
            state: $data['state'] ?? null,
            checkpoint: $data['checkpoint'] ?? null,
            lockedUntil: $data['locked_until'] ?? null,
        );
    }
}
