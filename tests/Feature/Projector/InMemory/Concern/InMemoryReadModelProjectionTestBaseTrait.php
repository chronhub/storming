<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Projector\InMemory\Concern;

use Closure;
use Storm\Contract\Projector\ProjectorManager;
use Storm\Contract\Projector\ReadModelProjector;
use Storm\Projector\Scope\ReadModelScope;
use Storm\Projector\Support\ReadModel\InMemoryReadModel;
use Storm\Tests\Domain\Balance\BalanceAdded;
use Storm\Tests\Domain\Balance\BalanceCreated;
use Storm\Tests\Domain\Balance\BalanceId;
use Storm\Tests\Domain\Balance\BalanceSubtracted;
use Storm\Tests\Feature\Projector\InMemory\Factory\InMemoryTestingFactory;

use function count;

trait InMemoryReadModelProjectionTestBaseTrait
{
    use BalanceEventStoreSetupTrait;

    protected ?InMemoryTestingFactory $factory = null;

    protected ?InMemoryReadModel $readModel = null;

    protected ?ReadModelProjector $projector = null;

    protected ?ProjectorManager $projectorManager = null;

    /**
     * @param array{string, ?BalanceId} $streamNames
     */
    protected function setupProjection(
        array $streamNames,
        string $projectionName,
        ?string $descriptionId = null,
        array $options = [],
    ): void {
        $this->projectorManager = $this->factory->createProjectorManager();
        $this->projector = $this->projectorManager->readModel($projectionName, $this->readModel, $options);

        foreach ($streamNames as [$streamName, $balanceId]) {
            $this->makeEventStore($streamName, $balanceId);
        }

        if ($descriptionId) {
            $this->projector->describe($descriptionId);
        }
    }

    protected function getReadModelReactor(): array
    {
        return [
            function (BalanceCreated $event) {
                /** @var ReadModelScope $this */
                $this->userState->increment('total', $event->amount());
                $this->readModel()->insert($event->id(), ['total' => $event->amount()]);
            },
            function (BalanceAdded $event) {
                /** @var ReadModelScope $this */
                $this->userState->increment('total', $event->amount());
                $this->readModel()->increment($event->id(), 'total', $event->amount());
            },
            function (BalanceSubtracted $event) {
                /** @var ReadModelScope $this */
                $this->userState->decrement('total', $event->amount());
                $this->readModel()->decrement($event->id(), 'total', $event->amount());
            },
        ];
    }

    protected function getThenReactor(bool $keepRunning = false, array $stopAt = []): Closure
    {
        return function (ReadModelScope $scope) use ($keepRunning, $stopAt) {
            if ($scope->event() === null) {
                return;
            }

            $scope->userState->push('events', $scope->event()::class);
            if (! $keepRunning || $stopAt === []) {
                return;
            }

            [$field, $expected] = $stopAt;
            if (count($scope->userState[$field]) === $expected) {
                $scope->stop();
            }
        };
    }

    protected function getIncrementUserStateReactor(): array
    {
        return [
            function (BalanceCreated $event) {
                /** @var ReadModelScope $this */
                $this->userState->set('total', $event->amount());
            },
            function (BalanceAdded $event) {
                /** @var ReadModelScope $this */
                $this->userState->increment('total', $event->amount());
            },
            function (BalanceSubtracted $event) {
                /** @var ReadModelScope $this */
                $this->userState->decrement('total', $event->amount());
            },
        ];
    }

    protected function assertReadModelBalance(string $streamName, int $total): void
    {
        expect($this->readModel->isInitialized())->toBeTrue();

        $container = $this->readModel->getContainer();
        expect($container)->toHaveKey(
            $this->eventStore[$streamName]->balanceId->toString(),
            ['total' => $total]
        );
    }

    protected function assertReadModelDown(): void
    {
        expect($this->readModel->isInitialized())->toBeTrue()
            ->and($this->readModel->getContainer())->toBeEmpty();

    }
}
