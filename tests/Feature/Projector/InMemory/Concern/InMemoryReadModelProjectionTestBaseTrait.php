<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Projector\InMemory\Concern;

use Storm\Contract\Projector\ProjectorManagerInterface;
use Storm\Contract\Projector\ReadModelProjector;
use Storm\Contract\Projector\ReadModelScope;
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

    protected ?ProjectorManagerInterface $projectorManager = null;

    protected function setupProjection(
        string $streamName,
        string $projectionName,
        ?string $descriptionId = null,
        array $options = [],
        ?BalanceId $balanceId = null
    ): void {
        $this->projectorManager = $this->factory->createProjectorManager();
        $this->projector = $this->projectorManager->newReadModelProjector($projectionName, $this->readModel, $options);

        $this->makeEventStore($streamName, $balanceId);

        if ($descriptionId) {
            $this->projector->describe($descriptionId);
        }
    }

    protected function getReadModelReactor(bool $keepRunning = false, array $stopAt = []): array
    {
        return [
            [
                function (BalanceCreated $event) {
                    /** @var ReadModelScope $this */
                    $this->userState->set('total', $event->amount());
                    $this->stack('insert', $event->id(), ['total' => $event->amount()]);
                },
                function (BalanceAdded $event) {
                    /** @var ReadModelScope $this */
                    $this->userState->increment('total', $event->amount());
                    $this->stack('increment', $event->id(), 'total', $event->amount());
                },
                function (BalanceSubtracted $event) {
                    /** @var ReadModelScope $this */
                    $this->userState->decrement('total', $event->amount());
                    $this->stack('decrement', $event->id(), 'total', $event->amount());
                },
            ],
            function (ReadModelScope $scope) use ($keepRunning, $stopAt) {
                $scope->userState->push('events', $scope->event()::class);
                if (! $keepRunning || $stopAt === []) {
                    return;
                }

                [$field, $expected] = $stopAt;
                if (count($scope->userState[$field]) === $expected) {
                    $scope->stop();
                }
            },
        ];
    }

    protected function getIncrementUserStateReactor(): array
    {
        return [
            [
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
            ],
            null,
        ];
    }

    protected function assertReadModelBalance(string $streamName, int $total): void
    {
        expect($this->readModel->isInitialized())->toBeTrue()
            ->and($this->readModel->getContainer())->toBe(
                [$this->eventStore[$streamName]->balanceId->toString() => ['total' => $total]]
            );
    }

    protected function assertReadModelDown(): void
    {
        expect($this->readModel->isInitialized())->toBeFalse()
            ->and($this->readModel->getContainer())->toBeEmpty();

    }
}
