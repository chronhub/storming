<?php

declare(strict_types=1);

namespace Storm\Attribute;

use Attribute;
use InvalidArgumentException;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
final readonly class Reference
{
    public function __construct(public string $name)
    {
        if (blank($this->name)) {
            throw new InvalidArgumentException('The name of the reference cannot be empty.');
        }
    }
}
