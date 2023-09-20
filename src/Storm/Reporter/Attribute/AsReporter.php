<?php

declare(strict_types=1);

namespace Storm\Reporter\Attribute;

use Attribute;
use Storm\Reporter\Filter\AllowsAll;
use Storm\Tracker\TrackMessage;

#[Attribute(Attribute::TARGET_CLASS)]
readonly class AsReporter
{
    public function __construct(
        public ?string $name = null,
        public string $filter = AllowsAll::class,
        public string $tracker = TrackMessage::class
    ) {
    }
}
