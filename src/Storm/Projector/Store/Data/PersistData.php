<?php

declare(strict_types=1);

namespace Storm\Projector\Store\Data;

final readonly class PersistData extends ProjectionData
{
    public function __construct(
        public string $state,
        public string $checkpoint,
        public string $lockedUntil
    ) {}

    /**
     * @return array{'state': string, 'checkpoint': string, 'locked_until': string}
     */
    public function toArray(): array
    {
        return [
            'state' => $this->state,
            'checkpoint' => $this->checkpoint,
            'locked_until' => $this->lockedUntil,
        ];
    }
}
