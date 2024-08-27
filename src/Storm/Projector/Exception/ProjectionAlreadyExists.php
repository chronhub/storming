<?php

declare(strict_types=1);

namespace Storm\Projector\Exception;

class ProjectionAlreadyExists extends RuntimeException
{
    public static function withName(string $name): self
    {
        return new self("Projection with name $name already exists");
    }
}
