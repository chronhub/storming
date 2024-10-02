<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Component;

class Sprint
{
    protected bool $inBackground = false;

    protected bool $inProgress = false;

    public function continue(): void
    {
        $this->inProgress = true;
    }

    public function halt(): void
    {
        $this->inProgress = false;
    }

    public function inProgress(): bool
    {
        return $this->inProgress;
    }

    public function runInBackground(bool $runInBackground): void
    {
        $this->inBackground = $runInBackground;
    }

    public function inBackground(): bool
    {
        return $this->inBackground;
    }
}
