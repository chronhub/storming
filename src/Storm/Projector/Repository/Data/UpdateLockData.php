<?php

declare(strict_types=1);

namespace Storm\Projector\Repository\Data;

final readonly class UpdateLockData extends ProjectionData
{
    public function __construct(public string $lockedUntil) {}

    /**
     * @return array{'locked_until': string}
     */
    public function toArray(): array
    {
        return ['locked_until' => $this->lockedUntil];
    }
}
