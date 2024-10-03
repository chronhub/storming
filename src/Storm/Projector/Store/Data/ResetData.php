<?php

declare(strict_types=1);

namespace Storm\Projector\Store\Data;

final readonly class ResetData extends ProjectionData
{
    public function __construct(
        public string $status,
        public string $state,
        public string $checkpoint
    ) {}

    /**
     * @return array{'status': string, 'state': string, 'checkpoint': string}
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'state' => $this->state,
            'checkpoint' => $this->checkpoint,
        ];
    }
}
