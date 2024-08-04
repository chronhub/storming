<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Factory;

use Storm\Contract\Projector\ProjectorScope;
use Storm\Tests\Domain\Balance\BalanceAdded;
use Storm\Tests\Domain\Balance\BalanceCreated;
use Storm\Tests\Domain\Balance\BalanceSubtracted;

class ReactorsStubs
{
    public function getReactors(): array
    {
        return [
            function (BalanceCreated $event) {
                /** @var ProjectorScope $this */
                $this->userState()->increment('balance', $event->amount());
            },
            function (BalanceAdded $event) {
                /** @var ProjectorScope $this */
                $this->userState()->increment('balance', $event->amount());
            },
            function (BalanceSubtracted $event) {
                /** @var ProjectorScope $this */
                $this->userState()->decrement('balance', $event->amount());
            },
        ];
    }
}
