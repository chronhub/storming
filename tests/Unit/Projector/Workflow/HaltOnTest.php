<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow;

use Storm\Projector\Checkpoint\GapType;
use Storm\Projector\Workflow\HaltOn;
use Storm\Projector\Workflow\Watcher\StopWatcher;

beforeEach(function () {
    $this->haltOn = new HaltOn();
});

dataset('integers', [
    'one integer' => [1],
    'two integers' => [5],
    'three integers' => [10],
]);

dataset('array of integers', [
    'one integer' => [[1]],
    'two integers' => [[1, 2]],
    'three integers' => [[1, 2, 3]],
]);

test('default instance', function () {
    expect($this->haltOn->callbacks())->toBeEmpty();
});

test('set requested callback', function (bool $requested) {
    $instance = $this->haltOn->whenRequested($requested);
    expect($instance)->toBeInstanceOf(HaltOn::class)
        ->and($instance->callbacks())->toHaveKey(StopWatcher::REQUESTED);

    $result = $instance->callbacks()[StopWatcher::REQUESTED]();
    expect($result)->toBe($requested);
})->with([
    ['requested' => true],
    ['not requested' => false],
]);

test('set signal received callback', function (array $signals) {
    $instance = $this->haltOn->whenSignalReceived($signals);
    expect($instance)->toBeInstanceOf(HaltOn::class)
        ->and($instance->callbacks())->toHaveKey(StopWatcher::SIGNAL_RECEIVED);

    $result = $instance->callbacks()[StopWatcher::SIGNAL_RECEIVED]();
    expect($result)->toBe($signals);
})->with('array of integers');

test('set empty event stream callback', function (int $expiredAt) {
    $instance = $this->haltOn->whenEmptyEventStream($expiredAt);
    expect($instance)->toBeInstanceOf(HaltOn::class)
        ->and($instance->callbacks())->toHaveKey(StopWatcher::EMPTY_EVENT_STREAM);

    $result = $instance->callbacks()[StopWatcher::EMPTY_EVENT_STREAM]();
    expect($result)->toBe($expiredAt);
})->with('integers');

test('set cycle reached callback', function (int $cycle) {
    $instance = $this->haltOn->whenCycleReached($cycle);
    expect($instance)->toBeInstanceOf(HaltOn::class)
        ->and($instance->callbacks())->toHaveKey(StopWatcher::CYCLE_REACHED);

    $result = $instance->callbacks()[StopWatcher::CYCLE_REACHED]();
    expect($result)->toBe($cycle);
})->with('integers');

test('set stream event limit reached callback', function (int $limit, bool $resetOnHalt) {
    $instance = $this->haltOn->whenStreamEventLimitReached($limit, $resetOnHalt);
    expect($instance)->toBeInstanceOf(HaltOn::class)
        ->and($instance->callbacks())->toHaveKey(StopWatcher::COUNTER_REACHED);

    $result = $instance->callbacks()[StopWatcher::COUNTER_REACHED]();

    expect($result)->toBe([$limit, $resetOnHalt]);
})
    ->with([
        ['limit of 10' => 10],
        ['limit of 100' => 100],
        ['limit of 1000' => 1000],
    ])
    ->with([
        ['reset on halt' => true],
        ['do not reset on halt' => false],
    ]);

test('set time expired callback', function (int $timestamp) {
    $instance = $this->haltOn->whenTimeExpired($timestamp);
    expect($instance)->toBeInstanceOf(HaltOn::class)
        ->and($instance->callbacks())->toHaveKey(StopWatcher::TIME_EXPIRED);

    $result = $instance->callbacks()[StopWatcher::TIME_EXPIRED]();
    expect($result)->toBe($timestamp);
})->with('integers');

it('set gap detected callback', function (GapType $gap) {
    $instance = $this->haltOn->whenGapDetected($gap);
    expect($instance)->toBeInstanceOf(HaltOn::class)
        ->and($instance->callbacks())->toHaveKey(StopWatcher::GAP_DETECTED);

    $result = $instance->callbacks()[StopWatcher::GAP_DETECTED]();
    expect($result)->toBe($gap);
})->with(['gap type' => [GapType::IN_GAP, GapType::UNRECOVERABLE_GAP, GapType::RECOVERABLE_GAP]]);
