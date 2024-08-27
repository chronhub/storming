<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Projector\InMemory\Concern;

use Closure;
use Storm\Contract\Projector\EmitterProjector;
use Storm\Contract\Projector\ProjectorManager;
use Storm\Projector\Scope\EmitterScope;
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

    protected ?ProjectorManager $projectorManager = null;

    /**
     * @param array{string, ?BalanceId} $streamNames
     */
    protected function setupProjection(
        array $streamNames,
        string $projectionName,
        ?string $descriptionId = null,
        array $options = [],
        ?string $connection = null,
    ): void {
        $this->projectorManager = $this->factory->createProjectorManager($connection);
        $this->projector = $this->projectorManager->emitter($projectionName, $options);

        foreach ($streamNames as [$streamName, $balanceId]) {
            $this->makeEventStore($streamName, $balanceId);
        }

        if ($descriptionId) {
            $this->projector->describe($descriptionId);
        }
    }

    protected function getEmitterReactor(): array
    {
        return [
            function (BalanceCreated $event) {
                /** @var EmitterScope $this */
                $this->userState->set('total', $event->amount());
            },
            function (BalanceAdded $event) {
                /** @var EmitterScope $this */
                $this->userState->increment('total', $event->amount());
            },
            function (BalanceSubtracted $event) {
                /** @var EmitterScope $this */
                $this->userState->decrement('total', $event->amount());
            },
        ];
    }

    protected function getThenReactor(?string $linkTo = null, bool $keepRunning = false, array $stopAt = []): Closure
    {
        return function (EmitterScope $scope) use ($linkTo, $keepRunning, $stopAt) {
            $event = $scope->event();

            is_string($linkTo)
                ? $scope->linkTo($linkTo, $event)
                : $scope->emit($event);

            if (! $keepRunning || $stopAt === []) {
                return;
            }

            [$field, $expected] = $stopAt;
            if ($scope->userState()[$field] === $expected) {
                $scope->stop();
            }
        };
    }
}
