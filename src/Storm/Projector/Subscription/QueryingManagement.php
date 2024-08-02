<?php

declare(strict_types=1);

namespace Storm\Projector\Subscription;

use Storm\Contract\Projector\QueryManagement;
use Storm\Projector\Workflow\Process;

final readonly class QueryingManagement implements QueryManagement
{
    public function __construct(private Process $process) {}

    public function performWhenThresholdIsReached(): void
    {
        if ($this->process->metrics()->isProcessedThresholdReached()) {
            $this->process->metrics()->reset('processed');
        }
    }
}
