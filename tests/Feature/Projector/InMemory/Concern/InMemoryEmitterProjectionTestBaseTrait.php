<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Projector\InMemory\Concern;

use Closure;
use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Projector\EmitterProjector;
use Storm\Contract\Projector\EmitterScope;
use Storm\Contract\Projector\ProjectorManagerInterface;
use Storm\Projector\Scope\EventScope;
use Storm\Projector\Scope\UserStateScope;
use Storm\Tests\Domain\Balance\BalanceAdded;
use Storm\Tests\Domain\Balance\BalanceCreated;
use Storm\Tests\Domain\Balance\BalanceId;
use Storm\Tests\Domain\Balance\BalanceSubtracted;
use Storm\Tests\Feature\Projector\InMemory\Factory\InMemoryTestingFactory;

use function is_string;

trait InMemoryEmitterProjectionTestBaseTrait
{
    use BalanceEventStoreSetupTrait;

    protected ?InMemoryTestingFactory $factory = null;

    protected ?EmitterProjector $projector = null;

    protected ?ProjectorManagerInterface $projectorManager = null;

    protected function setupProjection(
        string $streamName,
        string $projectionName,
        ?string $descriptionId = null,
        array $options = [],
        ?BalanceId $balanceId = null
    ): void {
        $this->projectorManager = $this->factory->createProjectorManager();
        $this->projector = $this->projectorManager->newEmitterProjector($projectionName, $options);

        $this->makeEventStore($streamName, $balanceId);

        if ($descriptionId) {
            $this->projector->describe($descriptionId);
        }
    }

    public function getEmitterReactor(?string $linkTo = null, bool $keepRunning = false, array $stopAt = []): Closure
    {
        return function (EventScope $scope) use ($linkTo, $keepRunning, $stopAt): void {
            $callback = function (DomainEvent $event, EmitterScope $scope, UserStateScope $userState) use ($linkTo, $keepRunning, $stopAt): void {
                $field = 'total';
                if ($event instanceof BalanceCreated || $event instanceof BalanceAdded) {
                    $userState->increment($field, $event->amount());
                }

                if ($event instanceof BalanceSubtracted) {
                    $userState->decrement($field, $event->amount());
                }

                is_string($linkTo)
                    ? $scope->linkTo($linkTo, $event)
                    : $scope->emit($event);

                if (! $keepRunning || $stopAt === []) {
                    return;
                }

                [$field, $expected] = $stopAt;
                if ($userState[$field] === $expected) {
                    $scope->stop();
                }
            };

            $scope
                ->ackOneOf(BalanceCreated::class, BalanceAdded::class, BalanceSubtracted::class)
                ?->then($callback);
        };
    }
}
