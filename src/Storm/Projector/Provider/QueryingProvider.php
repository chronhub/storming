<?php

declare(strict_types=1);

namespace Storm\Projector\Provider;

use Storm\Projector\Workflow\Process;

final readonly class QueryingProvider implements QueryProvider
{
    public function __construct(private Process $process) {}

    public function performWhenThresholdIsReached(): void
    {
        if ($this->process->metrics()->isProcessedThresholdReached()) {
            $this->process->metrics()->reset('processed');
        }
    }
}
