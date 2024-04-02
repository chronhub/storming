<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Stream;

use Storm\Contract\Projector\Subscriptor;

class CurrentProcessedStream
{
    public function __invoke(Subscriptor $subscriptor): string
    {
        return $subscriptor->getProcessedStream();
    }
}
