<?php

declare(strict_types=1);

namespace Storm\Projector\Storage\Data;

final readonly class UpdateStatusData extends ProjectionData
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
