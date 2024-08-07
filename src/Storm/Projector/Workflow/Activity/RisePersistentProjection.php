<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Activity;

use Storm\Projector\Provider\Events\ProjectionRise;
use Storm\Projector\Workflow\Process;

final readonly class RisePersistentProjection
{
    use MonitorRemoteStatus;

    protected bool $onRise;

    public function __construct()
    {
        $this->onRise = true;
    }

    public function __invoke(Process $process): bool
    {
        if ($process->metrics()->isFirstCycle()) {
            if ($this->discloseRemoteStatus($process)) {
                return false;
            }

            $process->dispatch(new ProjectionRise());
        }

        return true;
    }
}
