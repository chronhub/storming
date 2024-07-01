<?php

declare(strict_types=1);

namespace Storm\Tests\Domain;

use Closure;
use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Projector\EmitterScope;
use Storm\Contract\Projector\QueryProjectorScope;
use Storm\Contract\Projector\ReadModelScope;
use Storm\Projector\Scope\EventScope;
use Storm\Projector\Scope\UserStateScope;
use Storm\Tests\Domain\Balance\BalanceAdded;
use Storm\Tests\Domain\Balance\BalanceCreated;
use Storm\Tests\Domain\Balance\BalanceSubtracted;

use function count;
use function is_string;

class ProjectionBalanceReactor
{
    public static function getReadModelReactors(bool $keepRunning, int|false $stopAt): Closure
    {
        return function (EventScope $scope) use ($keepRunning, $stopAt): void {
            $reactors = function (DomainEvent $event, ReadModelScope $scope, UserStateScope $userState) use ($keepRunning, $stopAt): void {
                $id = $event->toContent()['id'];

                if ($event instanceof BalanceCreated) {
                    $userState->upsert('balance', $event->amount());
                    $scope->stack('insert', $id, ['balance' => $event->amount()]);
                }

                if ($event instanceof BalanceAdded) {
                    $userState->increment('balance', $event->amount());
                    $scope->stack('increment', $id, 'balance', $event->amount());
                }

                if ($event instanceof BalanceSubtracted) {
                    $userState->decrement('balance', $event->amount());
                    $scope->stack('decrement', $id, 'balance', $event->amount());
                }

                $userState->merge('events', [$event::class]);

                if ($keepRunning && count($userState['events']) === $stopAt) {
                    $scope->stop();
                }
            };

            $scope
                ->ackOneOf(BalanceCreated::class, BalanceAdded::class, BalanceSubtracted::class)
                ?->then($reactors);
        };
    }

    public static function getQueryReactors(bool $keepRunning, int|false $stopAt): Closure
    {
        return function (EventScope $scope) use ($keepRunning, $stopAt): void {
            $reactors = function (DomainEvent $event, QueryProjectorScope $scope, UserStateScope $userState) use ($keepRunning, $stopAt): void {
                $balanceId = $event->toContent()['id'];

                if ($event instanceof BalanceCreated) {
                    $userState[$balanceId] = $event->amount();
                }

                if ($event instanceof BalanceAdded) {
                    $userState[$balanceId] += $event->amount();
                }

                if ($event instanceof BalanceSubtracted) {
                    $userState[$balanceId] -= $event->amount();
                }

                $userState->merge('events', [$event::class]);

                if ($keepRunning && count($userState['events']) === $stopAt) {
                    $scope->stop();
                }
            };

            $scope
                ->ackOneOf(BalanceCreated::class, BalanceAdded::class, BalanceSubtracted::class)
                ?->then($reactors);
        };
    }

    public static function getEmitReactor(?string $linkTo): Closure
    {
        return function (EventScope $scope) use ($linkTo): void {
            $reactors = function (DomainEvent $event, EmitterScope $scope, UserStateScope $userState) use ($linkTo): void {
                if ($event instanceof BalanceCreated || $event instanceof BalanceAdded) {
                    $userState->increment('balance', $event->amount());
                }

                if ($event instanceof BalanceSubtracted) {
                    $userState->decrement('balance', $event->amount());
                }

                is_string($linkTo)
                    ? $scope->linkTo($linkTo, $event)
                    : $scope->emit($event);
            };

            $scope
                ->ackOneOf(BalanceCreated::class, BalanceAdded::class, BalanceSubtracted::class)
                ?->then($reactors);
        };
    }
}
