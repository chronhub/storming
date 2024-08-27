<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Projector\InMemory\Concern;

use Closure;
use Storm\Chronicler\Direction;
use Storm\Contract\Chronicler\InMemoryQueryFilter;
use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Projector\ProjectorManager;
use Storm\Contract\Projector\QueryProjector;
use Storm\Projector\Scope\QueryProjectorScope;
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

    protected ?ProjectorManager $projectorManager = null;

    protected function setupProjection(?string $descriptionId = null, array $options = []): void
    {
        $this->projectorManager = $this->factory->createProjectorManager();
        $this->projector = $this->projectorManager->query($options);

        if ($descriptionId) {
            $this->projector->describe($descriptionId);
        }
    }

    protected function getQueryReactor(): array
    {
        return [
            function (BalanceCreated $event) {
                /** @var QueryProjectorScope $this */
                $this->userState->set('balances.'.$event->id(), $event->amount());
            },
            function (BalanceAdded $event) {
                /** @var QueryProjectorScope $this */
                $this->userState->increment('balances.'.$event->id(), $event->amount());
            },
            function (BalanceSubtracted $event) {
                /** @var QueryProjectorScope $this */
                // for custom query filter
                if (! $this->userState->has('balances.'.$event->id())) {
                    $this->userState->set('balances.'.$event->id(), 0);
                }

                $this->userState->decrement('balances.'.$event->id(), $event->amount());
            },
        ];
    }

    protected function getThenReactor(bool $keepRunning = false, array $stopAt = []): Closure
    {
        return function (QueryProjectorScope $scope) use ($keepRunning, $stopAt) {
            $scope->userState->push('events', $scope->event()::class);

            if (! $keepRunning || $stopAt === []) {
                return;
            }

            // fixMe use closure
            [$field, $expected] = $stopAt;
            if ($field === 'events') {
                if (count($scope->userState[$field]) === $expected) {
                    $scope->stop();
                }
            } elseif ($scope->userState[$field] === $expected) {
                $scope->stop();
            }
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
