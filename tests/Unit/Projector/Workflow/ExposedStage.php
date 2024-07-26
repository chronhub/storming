<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow;

use Storm\Projector\Workflow\Stage;

final class ExposedStage extends Stage
{
    public function hasStarted(): bool
    {
        return $this->hasStarted;
    }

    public function getResetsOnEveryCycle(): array
    {
        return $this->resetsOnEveryCycle;
    }

    public function getResetsOnTermination(): array
    {
        return $this->resetsOnTermination;
    }

    public function getForgetsOnEveryCycle(): array
    {
        return $this->forgetsOnEveryCycle;
    }

    public function getForgetsOnTermination(): array
    {
        return $this->forgetsOnTermination;
    }
}
