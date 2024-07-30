<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Concern;

use Storm\Projector\Checkpoint\Checkpoint;
use Storm\Projector\Checkpoint\StreamPoint;
use Storm\Projector\Repository\ProjectionSnapshot;

trait ProvideAgentAccessor
{
    public function isFirstWorkflowCycle(): bool
    {
        return $this->stat()->cycle()->isFirst();
    }

    public function conditionallyStartWorkflow(): void
    {
        if (! $this->agentManager->stat()->cycle()->hasStarted()) {
            $this->agentManager->stat()->cycle()->next();

            $this->agentManager->time()->start();
        }
    }

    public function incrementWorkflowCycle(): void
    {
        $this->agentManager->stat()->cycle()->next();
    }

    public function discoverEventStream(): void
    {
        $query = $this->agentManager->context()->get()->query();
        $eventStreams = $this->agentManager->discovery()->discover($query);

        $this->agentManager->recognition()->track(...$eventStreams);
    }

    public function isUserStateInitialized(): bool
    {
        return $this->agentManager->context()->get()->isUserStateInitialized();
    }

    public function restoreUserState(): void
    {
        $initialState = $this->agentManager->context()->get()->userState();

        $this->agentManager->userState()->init($initialState);
    }

    public function processStreamEvent(StreamPoint $streamPoint): Checkpoint
    {
        return $this->agentManager->recognition()->record($streamPoint);
    }

    public function incrementBatchStream(): void
    {
        $this->agentManager->stat()->processed()->increment();

        $this->agentManager->stat()->main()->increment();
    }

    public function isBatchStreamBlank(): bool
    {
        if ($this->agentManager->stat()->processed()->count() !== 0) {
            return false;
        }

        return $this->agentManager->stat()->acked()->count() === 0;
    }

    // checkMe add boolean as argument to jsonSerialize or to array checkpoints
    public function takeSnapshot(): ProjectionSnapshot
    {
        return new ProjectionSnapshot(
            $this->agentManager->recognition()->jsonSerialize(),
            $this->agentManager->userState()->get()
        );
    }

    public function resetSnapshot(): void
    {
        $this->agentManager->recognition()->resets();

        $this->restoreUserState();
    }

    public function isSprintTerminated(): bool
    {
        return ! $this->agentManager->sprint()->inBackground()
            || ! $this->agentManager->sprint()->inProgress();
    }
}
