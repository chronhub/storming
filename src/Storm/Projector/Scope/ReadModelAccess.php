<?php

declare(strict_types=1);

namespace Storm\Projector\Scope;

use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Projector\ReadModel;
use Storm\Projector\Provider\Events\ProjectionClosed;
use Storm\Projector\Workflow\Process;

final class ReadModelAccess implements ReadModelScope
{
    use ScopeAccess;

    public function __construct(
        protected Process $process,
        protected SystemClock $clock,
        public readonly ReadModel $readModel,
        public ?UserState $userState = null,
    ) {}

    public function readModel(): ReadModel
    {
        return $this->readModel;
    }

    public function stop(): void
    {
        $this->process->dispatch(new ProjectionClosed);
    }
}
