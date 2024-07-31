<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Input;

use Storm\Contract\Projector\Component;

final class DiscoverEventStream
{
    public function __invoke(Component $component): void
    {
        $query = $component->context()->get()->query();
        $eventStreams = $component->discovery()->discover($query);

        $component->recognition()->track(...$eventStreams);
    }
}
