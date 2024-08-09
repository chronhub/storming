<?php

declare(strict_types=1);

namespace Storm\Projector\Scope;

use Storm\Contract\Clock\SystemClock;
use Storm\Projector\Workflow\Process;

final class QueryAccess implements QueryProjectorScope
{
    use ScopeAccess;

    public function __construct(
        protected readonly Process $process,
        protected readonly SystemClock $clock,
        public ?UserState $userState = null,
    ) {}

    public function stop(): void
    {
        $this->process->sprint()->halt();
    }
}
