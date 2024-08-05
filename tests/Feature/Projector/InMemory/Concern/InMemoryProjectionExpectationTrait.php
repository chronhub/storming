<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Projector\InMemory\Concern;

use Storm\Contract\Message\EventHeader;
use Storm\Contract\Projector\ProjectionModel;
use Storm\Contract\Projector\Projector;
use Storm\Stream\StreamName;
use Storm\Tests\Domain\Balance\BalanceId;
use Storm\Tests\Feature\Projector\InMemory\Factory\InMemoryTestingFactory;

use function is_string;
use function iterator_to_array;

/**
 * @property InMemoryTestingFactory $factory
 * @property Projector              $projector
 */
trait InMemoryProjectionExpectationTrait
{
    protected function assertStreamExists(StreamName|string $streamName, bool $exists): void
    {
        if (is_string($streamName)) {
            $streamName = new StreamName($streamName);
        }

        expect($this->factory->getEventStore()->hasStream($streamName))->toBe($exists);
    }

    protected function assertInternalPositionsOfStream(string $streamName, BalanceId $balanceId, array $expectedPositions): void
    {
        $streamEvents = $this->factory->getEventStore()->retrieveAll(new StreamName($streamName), $balanceId);
        $events = iterator_to_array($streamEvents);

        $internalPositions = [];
        foreach ($events as $event) {
            $internalPositions[] = $event->header(EventHeader::INTERNAL_POSITION);
        }

        expect($expectedPositions)->toBe($internalPositions);
    }

    protected function assertProjectionExists(string $projectionName, bool $exists): void
    {
        expect($this->factory->getProjectionProvider()->exists($projectionName))->toBe($exists);
    }

    protected function assertProjectionState(array $userState): void
    {
        expect($this->projector->getState())->toBe($userState);
    }

    protected function assertPartialProjectionState(mixed $field, mixed $value): void
    {
        expect($this->projector->getState())->toHaveKey($field, $value);
    }

    protected function assertProjectionModel(
        string $projectionName,
        string $status,
        ?string $lockedUntil,
    ): void {
        $this->assertProjectionExists($projectionName, true);

        $projection = $this->factory->getProjectionProvider()->retrieve($projectionName);
        expect($projection)->toBeInstanceOf(ProjectionModel::class);

        $userState = $this->projector->getState();
        $encodedState = $this->factory->getSerializer()->encode($userState, 'json');

        expect($projection->state())->toBe($encodedState)
            ->and($projection->name())->toBe($projectionName)
            ->and($projection->status())->toBe($status)
            ->and($projection->lockedUntil())->toBe($lockedUntil);
    }

    protected function assertProjectionModelCheckpoint(
        string $projectionName,
        string $streamName,
        int $position,
        array $gaps = []
    ): void {
        $this->assertProjectionExists($projectionName, true);

        $projection = $this->factory->getProjectionProvider()->retrieve($projectionName);
        $checkpoint = $this->factory->getSerializer()->decode($projection->checkpoint(), 'json');
        $streamCheckpoint = $checkpoint[$streamName];

        expect($streamCheckpoint)->toHaveKey('position', $position)
            ->and($streamCheckpoint)->toHaveKey('gaps', $gaps)
            ->and($streamCheckpoint)->toHaveKey('gap_type', null)
            ->and($streamCheckpoint['event_time'])->toBeString()
            ->and($streamCheckpoint['created_at'])->toBeString();
    }

    protected function assertProjectionReport(
        int $cycle,
        int $ackedEvent,
        int $totalEvent,
        ?string $descriptionId = null
    ): void {
        $report = $this->projector->getReport();

        expect($report['cycle'])->toBe($cycle)
            ->and($report['acked_event'])->toBe($ackedEvent)
            ->and($report['total_event'])->toBe($totalEvent)
            ->and($report['projection_id'])->toBe($descriptionId ?? $this->projector::class);
    }

    protected function assertPartialProjectionReport(array $partialReport): void
    {
        $report = $this->projector->getReport();

        foreach ($partialReport as $key => $value) {
            expect($report)->toHaveKey($key, $value);
        }
    }

    /**
     * @param  int<0, max> $numberOfEventWithNoGap
     * @param  int<0, max> $numberOfRetry
     * @param  int<0, max> $numberOfEventWithGap
     * @return int<0, max>
     */
    protected function calculateExpectedCycles(
        int $numberOfEventWithNoGap,
        int $numberOfRetry,
        int $numberOfEventWithGap): int
    {
        return $numberOfEventWithNoGap + $numberOfRetry * $numberOfEventWithGap;
    }
}
