<?php

declare(strict_types=1);

namespace Storm\Projector\Repository\Data;

use Storm\Contract\Projector\ProjectionData;

abstract readonly class ProjectionDTO implements ProjectionData
{
    // todo remove interface implementation and add abstract method toArray
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
