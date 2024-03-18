<?php

declare(strict_types=1);

namespace Storm\Projector\Support\Notification\Stream;

use Iterator;
use Storm\Contract\Projector\Subscriptor;

final class PullStreamIterator
{
    public function __invoke(Subscriptor $subscriptor): ?Iterator
    {
        return $subscriptor->pullStreamIterator();
    }
}
