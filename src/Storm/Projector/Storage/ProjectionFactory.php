<?php

declare(strict_types=1);

namespace Storm\Projector\Storage;

use Storm\Contract\Projector\ProjectionModel;

use function is_array;

/**
 * @phpstan-type ProjectionArray array{
 *      name: string,
 *      status: string,
 *      checkpoint?: string|null,
 *      state?: string|null,
 *      locked_until?: string|null,
 *  }
 * @phpstan-type ProjectionObject object{
 *      name: string,
 *      status: string,
 *      state?: string|null,
 *      checkpoint?: string|null,
 *      locked_until?: string|null,
 *  }
 */
class ProjectionFactory
{
    /**
     * The default projection model class.
     */
    public static string $model = Projection::class;

    /**
     * Create a new projection model.
     */
    public static function create(string $name, string $status): ProjectionModel
    {
        return new self::$model($name, $status, null, null, null);
    }

    /**
     * Create a new projection model from the given data.
     *
     * @param ProjectionArray|ProjectionObject $data
     */
    public static function make(array|object $data): ProjectionModel
    {
        return is_array($data) ? self::fromArray($data) : self::fromObject($data);
    }

    /**
     * Create a new projection model from the given object.
     *
     * @param ProjectionObject $data
     */
    public static function fromObject(object $data): ProjectionModel
    {
        return new self::$model(
            name: $data->name,
            status: $data->status,
            state: $data->state ?? null,
            checkpoint: $data->checkpoint ?? null,
            lockedUntil: $data->locked_until ?? null,
        );
    }

    /**
     * Create a new projection model from the given array.
     *
     * @param ProjectionArray $data
     */
    public static function fromArray(array $data): ProjectionModel
    {
        return new self::$model(
            name: $data['name'],
            status: $data['status'],
            state: $data['state'] ?? null,
            checkpoint: $data['checkpoint'] ?? null,
            lockedUntil: $data['locked_until'] ?? null,
        );
    }
}
