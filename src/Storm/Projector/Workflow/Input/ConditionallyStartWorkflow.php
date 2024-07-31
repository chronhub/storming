<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Input;

use Storm\Contract\Projector\Component;

final class ConditionallyStartWorkflow
{
    public function __invoke(Component $component): void
    {
        if (! $component->metrics()->cycle > 0) {
            $component->metrics()->cycle++;

            $component->time()->start();
        }
    }
}
