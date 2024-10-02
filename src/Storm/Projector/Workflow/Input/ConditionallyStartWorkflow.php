<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Input;

use Storm\Projector\Factory\Component\ComponentManager;

final class ConditionallyStartWorkflow
{
    public function __invoke(ComponentManager $component): void
    {
        if ($component->metrics()->cycle < 1) {
            $component->metrics()->cycle++;

            $component->time()->start();
        }
    }
}
