<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Activity;

use Storm\Projector\Workflow\WorkflowContext;

use function pcntl_signal_dispatch;

final readonly class DispatchSignal
{
    public function __construct(private bool $dispatchSignal) {}

    public function __invoke(WorkflowContext $context): bool
    {
        if ($this->dispatchSignal) {
            pcntl_signal_dispatch();
        }

        return true;
    }
}
