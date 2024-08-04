<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Factory;

use Storm\Contract\Projector\ProjectorScope;
use Storm\Projector\Scope\EventScopeFactory;
use Storm\Tests\Domain\Balance\BalanceCreated;
use Storm\Tests\Domain\Balance\BalanceId;
use Storm\Tests\Domain\Balance\BalanceSubtracted;

test('events', function () {
    $reactors = (new ReactorsStubs())->getReactors();
    $projector = mock(ProjectorScope::class);

    $factory = new EventScopeFactory($reactors, $projector);

    $event = BalanceCreated::withBalance(BalanceId::create(), 1000);
    $eventScope = $factory->handle($event, ['balance' => 0]);

    $event = BalanceSubtracted::withBalance(BalanceId::create(), 100);
    $eventScope = $factory->handle($event, $eventScope->userState->state());

    $event = BalanceSubtracted::withBalance(BalanceId::create(), 500);
    $eventScope = $factory->handle($event, $eventScope->userState->state());

    dump($eventScope);
    //dump($factory->getEvents());
});
