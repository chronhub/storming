<?php

declare(strict_types=1);

namespace Storm\Reporter\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class AsMessageHandler
{
}
