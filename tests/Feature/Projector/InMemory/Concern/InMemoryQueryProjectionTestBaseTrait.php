<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Projector\InMemory\Concern;

use Closure;
use Storm\Chronicler\Direction;
use Storm\Contract\Chronicler\InMemoryQueryFilter;
use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Projector\ProjectorManagerInterface;
use Storm\Contract\Projector\QueryProjector;
use Storm\Contract\Projector\QueryProjectorScope;
use Storm\Projector\Scope\EventScope;
use Storm\Projector\Scope\UserStateScope;
use Storm\Tests\Domain\Balance\BalanceAdded;
use Storm\Tests\Domain\Balance\BalanceCreated;
use Storm\Tests\Domain\Balance\BalanceSubtracted;
use Storm\Tests\Feature\Projector\InMemory\Factory\InMemoryTestingFactory;

use function count;
use function in_array;

trait InMemoryQueryProjectionTestBaseTrait
{
    use BalanceEventStoreSetupTrait;

    protected ?InMemoryTestingFactory $factory = null;

    protected ?QueryProjector $projector = null;

    protected ?ProjectorManagerInterface $projectorManager = null;

    protected function setupProjection(?string $descriptionId = null, array $options = []): void
    {
        $this->projectorManager = $this->factory->createProjectorManager();
        $this->projector = $this->projectorManager->newQueryProjector($options);

        if ($descriptionId) {
            $this->projector->describe($descriptionId);
        }
    }

    protected function getQueryReactor(bool $keepRunning = false, array $stopAt = []): Closure
    {
        return function (EventScope $scope) use ($keepRunning, $stopAt): void {
            $callback = function (DomainEvent $event, QueryProjectorScope $scope, UserStateScope $userState) use ($keepRunning, $stopAt): void {
                $balanceId = $event->toContent()['id'];

                if ($event instanceof BalanceCreated || $event instanceof BalanceAdded) {
                    $userState->increment('balances.'.$balanceId, $event->amount());
                }

                if ($event instanceof BalanceSubtracted) {
                    $userState->decrement('balances.'.$balanceId, $event->amount());
                }

                $userState->merge('events', [$event::class]);

                if (! $keepRunning || $stopAt === []) {
                    return;
                }

                // fixMe use closure
                [$field, $expected] = $stopAt;
                if ($field === 'events') {
                    if (count($userState[$field]) === $expected) {
                        $scope->stop();
                    }
                } elseif ($userState[$field] === $expected) {
                    $scope->stop();
                }
            };

            $scope
                ->ackOneOf(BalanceCreated::class, BalanceAdded::class, BalanceSubtracted::class)
                ?->then($callback);
        };
    }

    protected function customFilterQueryEvents(string ...$events): InMemoryQueryFilter
    {
        return new readonly class($events) implements InMemoryQueryFilter
        {
            public function __construct(private array $events) {}

            public function orderBy(): Direction
            {
                return Direction::FORWARD;
            }

            public function apply(): callable
            {
                return fn (DomainEvent $event): bool => in_array($event::class, $this->events);
            }
        };
    }
}
