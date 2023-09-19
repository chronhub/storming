<?php

declare(strict_types=1);

namespace Storm\Reporter\Attribute;

use Attribute;
use Storm\Contract\Reporter\MessageFilter;
use Storm\Contract\Tracker\MessageTracker;
use Storm\Reporter\Filter\AllowsAll;
use Storm\Tracker\TrackMessage;

use function is_string;

#[Attribute(Attribute::TARGET_CLASS)]
readonly class AsReporter
{
    public function __construct(
        public ?string $name = null,
        public string|MessageFilter $filter = AllowsAll::class,
        public string|MessageTracker $tracker = TrackMessage::class
    ) {
    }

    public function getStringFilter(): string
    {
        return is_string($this->filter) ? $this->filter : $this->filter::class;
    }

    public function getStringTracker(): string
    {
        return is_string($this->tracker) ? $this->tracker : $this->tracker::class;
    }
}
