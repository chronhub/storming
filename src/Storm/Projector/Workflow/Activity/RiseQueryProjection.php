<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Activity;

use Storm\Projector\Workflow\Input\DiscoverEventStream;
use Storm\Projector\Workflow\Process;

final readonly class RiseQueryProjection
{
    public function __invoke(Process $projection): void
    {
        if ($projection->metrics()->isFirstCycle()) {
            $projection->call(new DiscoverEventStream());
        }
    }
}
