<?php

declare(strict_types=1);

namespace Storm\Projector\Support\Notification\MasterCounter;

use Storm\Contract\Projector\Subscriptor;

final readonly class KeepMasterCounterOnStop
{
    public function __construct(public bool $keepMasterCounterOnStop = true)
    {
    }

    public function __invoke(Subscriptor $subscriptor): void
    {
        $subscriptor->watcher()->masterCounter()->doNotReset($this->keepMasterCounterOnStop);
    }
}
