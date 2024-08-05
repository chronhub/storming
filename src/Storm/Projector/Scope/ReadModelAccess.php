<?php

declare(strict_types=1);

namespace Storm\Projector\Scope;

use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Projector\ReadModel;
use Storm\Projector\Workflow\Management\ProjectionClosed;
use Storm\Projector\Workflow\Process;

final class ReadModelAccess implements ReadModelScope
{
    use BoundScope;

    public function __construct(
        protected Process $process,
        protected SystemClock $clock,
        public readonly ReadModel $readModel,
        public ?UserStateScope $userState = null,
    ) {}

    public function readModel(): ReadModel
    {
        return $this->readModel;
    }

    public function stack(string $operation, mixed ...$arguments): self
    {
        $this->readModel->stack($operation, ...$arguments);

        return $this;
    }

    public function stop(): void
    {
        $this->process->dispatch(new ProjectionClosed());
    }
}
