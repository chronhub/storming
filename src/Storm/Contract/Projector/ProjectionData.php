<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

use JsonSerializable;

interface ProjectionData extends JsonSerializable
{
    public function toArray(): array;
}
