<?php

declare(strict_types=1);

namespace Storm\Projector\Scope;

use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Projector\QueryProjectorScope;
use Storm\Projector\Workflow\Process;

final readonly class QueryAccess implements QueryProjectorScope
{
    public function __construct(
        private Process $process,
        private SystemClock $clock
    ) {}

    public function stop(): void
    {
        $this->process->sprint()->halt();
    }

    public function streamName(): string
    {
        return $this->process->stream()->get();
    }

    public function clock(): SystemClock
    {
        return $this->clock;
    }
}
