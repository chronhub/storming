<?php

declare(strict_types=1);

namespace Storm\Projector;

use Storm\Contract\Chronicler\QueryFilter;
use Storm\Contract\Projector\ContextReader;
use Storm\Contract\Projector\QueryProjector;
use Storm\Projector\Provider\Manager;
use Storm\Projector\Workflow\Input\ResetSnapshot;
use Storm\Projector\Workflow\Process;

final readonly class ProjectQuery implements QueryProjector
{
    use InteractWithProjection;

    public function __construct(
        protected Manager $manager,
        protected ContextReader $context,
    ) {}

    public function run(bool $inBackground): void
    {
        $this->describeIfNeeded();

        $this->manager->start($this->context, $inBackground);
    }

    public function stop(): void
    {
        $this->manager->call(fn (Process $process) => $process->sprint()->halt());
    }

    public function reset(): void
    {
        $this->manager->call(fn (Process $process) => $process->call(new ResetSnapshot)
        );
    }

    public function filter(QueryFilter $queryFilter): static
    {
        $this->context->withQueryFilter($queryFilter);

        return $this;
    }
}
