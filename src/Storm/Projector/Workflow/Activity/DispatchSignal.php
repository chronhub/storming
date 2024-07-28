<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Activity;

use Storm\Contract\Projector\NotificationHub;

use function pcntl_signal_dispatch;

final readonly class DispatchSignal
{
    public function __construct(private bool $dispatchSignal) {}

    public function __invoke(NotificationHub $hub): bool
    {
        if ($this->dispatchSignal) {
            pcntl_signal_dispatch();
        }

        return true;
    }
}
