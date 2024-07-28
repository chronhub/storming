<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Agent;

class SprintAgent
{
    protected bool $inBackground = false;

    protected bool $inProgress = false;

    /**
     * Start the sprint.
     */
    public function continue(): void
    {
        $this->inProgress = true;
    }

    /**
     * Halt the sprint.
     */
    public function halt(): void
    {
        $this->inProgress = false;
    }

    /**
     * Check if the sprint is in progress.
     */
    public function inProgress(): bool
    {
        return $this->inProgress;
    }

    /**
     * Set the sprint is running once or in the background.
     */
    public function runInBackground(bool $runInBackground): void
    {
        $this->inBackground = $runInBackground;
    }

    /**
     * Check if the sprint is running once or in the background.
     */
    public function inBackground(): bool
    {
        return $this->inBackground;
    }
}
