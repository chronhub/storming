<?php

declare(strict_types=1);

namespace Storm\Support\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
final readonly class Reference
{
    public function __construct(public string $name = '')
    {
    }
}
