<?php

declare(strict_types=1);

namespace Storm\Projector\Repository\Data;

use Storm\Contract\Projector\ProjectionData;

abstract readonly class ProjectionDTO implements ProjectionData
{
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
