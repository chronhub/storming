<?php

declare(strict_types=1);

namespace Storm\Projector\Repository\Data;

final readonly class CreateData extends ProjectionData
{
    public function __construct(public string $status) {}

    /**
     * @return array{'status': string}
     */
    public function toArray(): array
    {
        return ['status' => $this->status];
    }
}
