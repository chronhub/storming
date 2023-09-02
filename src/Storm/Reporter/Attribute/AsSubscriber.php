<?php

declare(strict_types=1);

namespace Storm\Reporter\Attribute;

use Attribute;

/**
 * when subscriber could be attached to message&stream tracker
 * suggest your own method
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class AsSubscriber
{
    public function __construct(
        string $eventName,
        int $priority = 1,
        string $method = null)
    {
    }
}
