<?php

declare(strict_types=1);

namespace Storm\Projector\Repository\Data;

final readonly class StartAgainData extends ProjectionDTO
{
    public function __construct(public string $status, public string $lockedUntil)
    {
    }

    /**
     * @return array{'status': string, 'locked_until': string}
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'locked_until' => $this->lockedUntil,
        ];
    }
}
