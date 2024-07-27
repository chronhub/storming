<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Projector\InMemory\Concern;

use Closure;
use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Projector\ProjectorManagerInterface;
use Storm\Contract\Projector\ReadModelProjector;
use Storm\Contract\Projector\ReadModelScope;
use Storm\Projector\Scope\EventScope;
use Storm\Projector\Scope\UserStateScope;
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

    protected function getReadModelReactor(bool $keepRunning = false, array $stopAt = []): Closure
    {
        return function (EventScope $scope) use ($keepRunning, $stopAt): void {
            $callback = function (DomainEvent $event, ReadModelScope $scope, UserStateScope $userState) use ($keepRunning, $stopAt): void {
                $field = 'total';
                $id = $event->toContent()['id'];

                if ($event instanceof BalanceCreated) {
                    $userState->upsert($field, $event->amount());
                    $scope->stack('insert', $id, [$field => $event->amount()]);
                }

                if ($event instanceof BalanceAdded) {
                    $userState->increment($field, $event->amount());
                    $scope->stack('increment', $id, $field, $event->amount());
                }

                if ($event instanceof BalanceSubtracted) {
                    $userState->decrement($field, $event->amount());
                    $scope->stack('decrement', $id, $field, $event->amount());
                }

                $userState->merge('events', [$event::class]);

                if (! $keepRunning || $stopAt === []) {
                    return;
                }

                [$field, $expected] = $stopAt;
                if (count($userState[$field]) === $expected) {
                    $scope->stop();
                }
            };

            $scope
                ->ackOneOf(BalanceCreated::class, BalanceAdded::class, BalanceSubtracted::class)
                ?->then($callback);
        };
    }

    protected function getIncrementUserStateReactor(): Closure
    {
        return function (EventScope $scope): void {
            $scope
                ->ackOneOf(BalanceCreated::class, BalanceAdded::class, BalanceSubtracted::class)
                ->then(function (DomainEvent $event, ReadModelScope $scope, UserStateScope $userState): void {
                    ! $event instanceof BalanceSubtracted
                        ? $userState->increment('total', $event->toContent()['amount'])
                        : $userState->decrement('total', $event->toContent()['amount']);
                });
        };
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
