<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Input;

use Storm\Projector\Workflow\ComponentRegistry;

final class ConditionallyStartWorkflow
{
    public function __invoke(ComponentRegistry $component): void
    {
        if ($component->metrics()->cycle < 1) {
            $component->metrics()->cycle++;

            $component->time()->start();
        }
    }
}
