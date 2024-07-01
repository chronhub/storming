<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Stream;

use Storm\Contract\Projector\Subscriptor;
use Storm\Projector\Iterator\MergeStreamIterator;

final readonly class StreamIteratorSet
{
    public function __construct(public ?MergeStreamIterator $iterator) {}

    public function __invoke(Subscriptor $subscriptor): void
    {
        $subscriptor->setStreamIterator($this->iterator);
    }
}
