<?php

declare(strict_types=1);

namespace Storm\Reporter\Attribute;

use Attribute;
use Storm\Contract\Reporter\MessageFilter;
use Storm\Contract\Tracker\MessageTracker;
use Storm\Reporter\Filter\AllowsAll;
use Storm\Tracker\TrackMessage;

#[Attribute(Attribute::TARGET_CLASS)]
readonly class AsReporter
{
    public function __construct(
        /**
         * @var string|null class name or service id (should be used to bind reporter and add header for async)
         */
        public ?string $name = null,
        public string|MessageFilter $filter = new AllowsAll(),
        public string|MessageTracker $tracker = new TrackMessage() // todo remove and use reference
    ) {
    }
}
