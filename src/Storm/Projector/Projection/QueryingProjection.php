<?php

declare(strict_types=1);

namespace Storm\Projector\Projection;

use Storm\Projector\Workflow\Process;

final readonly class QueryingProjection implements QueryProjection
{
    public function __construct(private Process $process) {}

    public function performWhenThresholdIsReached(): void
    {
        if ($this->process->metrics()->isProcessedThresholdReached()) {
            $this->process->metrics()->reset('processed');
        }
    }
}
