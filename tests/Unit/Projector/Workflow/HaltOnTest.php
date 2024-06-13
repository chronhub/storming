<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow;

use Storm\Projector\Checkpoint\GapType;
use Storm\Projector\Workflow\HaltOn;
use Storm\Projector\Workflow\Watcher\StopWatcher;

beforeEach(function () {
    $this->haltOn = new HaltOn();
});

it('test default instance', function () {
    expect($this->haltOn->callbacks())->toBeEmpty();
});

it('set when requested callback', function (bool $requested) {
    $instance = $this->haltOn->whenRequested($requested);
    expect($instance)->toBeInstanceOf(HaltOn::class)
        ->and($instance->callbacks())->toHaveKey(StopWatcher::REQUESTED);

    $result = $instance->callbacks()[StopWatcher::REQUESTED]();
    expect($result)->toBe($requested);
})->with(['boolean' => [true, false]]);

it('set when signal received callback', function (array $signals) {
    $instance = $this->haltOn->whenSignalReceived($signals);
    expect($instance)->toBeInstanceOf(HaltOn::class)
        ->and($instance->callbacks())->toHaveKey(StopWatcher::SIGNAL_RECEIVED);

    $result = $instance->callbacks()[StopWatcher::SIGNAL_RECEIVED]();
    expect($result)->toBe($signals);
})->with(['integers signals' => [[1, 2, 3], [4, 5, 6]]]);

it('set when empty event stream callback', function (int $expiredAt) {
    $instance = $this->haltOn->whenEmptyEventStream($expiredAt);
    expect($instance)->toBeInstanceOf(HaltOn::class)
        ->and($instance->callbacks())->toHaveKey(StopWatcher::EMPTY_EVENT_STREAM);

    $result = $instance->callbacks()[StopWatcher::EMPTY_EVENT_STREAM]();
    expect($result)->toBe($expiredAt);
})->with(['integer' => [1, 2, 3]]);

it('set when cycle reached callback', function (int $cycle) {
    $instance = $this->haltOn->whenCycleReached($cycle);
    expect($instance)->toBeInstanceOf(HaltOn::class)
        ->and($instance->callbacks())->toHaveKey(StopWatcher::CYCLE_REACHED);

    $result = $instance->callbacks()[StopWatcher::CYCLE_REACHED]();
    expect($result)->toBe($cycle);
})->with(['integer' => [1, 2, 3]]);

it('set when stream event limit reached callback', function (bool $resetOnHalt) {
    $instance = $this->haltOn->whenStreamEventLimitReached(10);
    expect($instance)->toBeInstanceOf(HaltOn::class)
        ->and($instance->callbacks())->toHaveKey(StopWatcher::COUNTER_REACHED);

    $result = $instance->callbacks()[StopWatcher::COUNTER_REACHED]();
    expect($result)->toBe([10, $resetOnHalt]);
})->with(['reset on halt' => [true, false]]);

it('set when time expired callback', function (int $expiredAt) {
    $instance = $this->haltOn->whenTimeExpired($expiredAt);
    expect($instance)->toBeInstanceOf(HaltOn::class)
        ->and($instance->callbacks())->toHaveKey(StopWatcher::TIME_EXPIRED);

    $result = $instance->callbacks()[StopWatcher::TIME_EXPIRED]();
    expect($result)->toBe($expiredAt);
})->with(['timestamps' => [1, 2, 3]]);

it('set when gap detected callback', function (GapType $gap) {
    $instance = $this->haltOn->whenGapDetected($gap);
    expect($instance)->toBeInstanceOf(HaltOn::class)
        ->and($instance->callbacks())->toHaveKey(StopWatcher::GAP_DETECTED);

    $result = $instance->callbacks()[StopWatcher::GAP_DETECTED]();
    expect($result)->toBe($gap);
})->with(['gap type' => [GapType::IN_GAP, GapType::UNRECOVERABLE_GAP, GapType::RECOVERABLE_GAP]]);
