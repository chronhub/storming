<?php

declare(strict_types=1);

namespace Storm\Reporter\Attribute;

use Attribute;
use Storm\Contract\Tracker\Tracker;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
readonly class AsSubscriber
{
    public function __construct(
        public string $eventName,
        public int $priority = Tracker::DEFAULT_PRIORITY,
        public string $method = '__invoke'
    ) {
    }
}
