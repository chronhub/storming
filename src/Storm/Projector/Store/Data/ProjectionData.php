<?php

declare(strict_types=1);

namespace Storm\Projector\Store\Data;

use JsonSerializable;

abstract readonly class ProjectionData implements JsonSerializable
{
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    abstract public function toArray(): array;
}
