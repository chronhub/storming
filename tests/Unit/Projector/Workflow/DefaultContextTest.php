<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow;

use Closure;
use Storm\Contract\Chronicler\QueryFilter;
use Storm\Projector\Repository\EventStream\DiscoverAllStream;
use Storm\Projector\Repository\EventStream\DiscoverCategories;
use Storm\Projector\Repository\EventStream\DiscoverStream;
use Storm\Projector\Workflow\DefaultContext;
use Storm\Projector\Workflow\HaltOn;

beforeEach(function () {
    $this->context = new DefaultContext();
});

it('initialize the context', function () {
    $this->context->initialize(fn () => []);

    expect($this->context->userState())->toBeInstanceOf(Closure::class);
});

it('raise exception when context is already initialized', function () {
    $this->context->initialize(fn () => []);

    $this->expectExceptionMessage('Projection already initialized');
    $this->context->initialize(fn () => []);
});

it('initialize the context with query filter', function () {
    $queryFilter = mock(QueryFilter::class);
    $this->context->withQueryFilter($queryFilter);

    expect($this->context->queryFilter())->toBe($queryFilter);
});

it('raise exception when query filter is already set', function () {
    $queryFilter = mock(QueryFilter::class);
    $this->context->withQueryFilter($queryFilter);

    $this->expectExceptionMessage('Projection query filter already set');
    $this->context->withQueryFilter($queryFilter);
});

it('initialize the context with keep state', function () {
    $this->context->withKeepState();

    expect($this->context->keepState())->toBeTrue();
});

it('raise exception when keep state is already set', function () {
    $this->context->withKeepState();

    $this->expectExceptionMessage('Projection keep state already set');
    $this->context->withKeepState();
});

it('set id', function () {
    $this->context->withId('id');

    expect($this->context->id())->toBe('id');
});

it('raise exception when id is already set', function () {
    $this->context->withId('id');

    $this->expectExceptionMessage('Projection id already set');
    $this->context->withId('id');
});

it('subscribe to stream', function () {
    $this->context->subscribeToStream('stream-1');

    $query = $this->context->queries();
    expect($query)->toBeInstanceOf(DiscoverStream::class)
        ->and($query->streams)->toBe(['stream-1']);
});

it('subscribe to category', function () {
    $this->context->subscribeToCategory('category-1');

    $query = $this->context->queries();
    expect($query)->toBeInstanceOf(DiscoverCategories::class)
        ->and($query->categories)->toBe(['category-1']);
});

it('subscribe to all stream', function () {
    $this->context->subscribeToAll();

    $query = $this->context->queries();
    expect($query)->toBeInstanceOf(DiscoverAllStream::class);
});

it('raise exception when query stream is already set', function () {
    $this->context->subscribeToStream('stream-1');

    $this->expectExceptionMessage('Projection query already set');
    $this->context->subscribeToStream('stream-2');
});

it('raise exception when query categories is already set', function () {
    $this->context->subscribeToCategory('category-1');

    $this->expectExceptionMessage('Projection query already set');
    $this->context->subscribeToCategory('category-2');
});

it('raise exception when query all stream is already set', function () {
    $this->context->subscribeToAll();

    $this->expectExceptionMessage('Projection query already set');
    $this->context->subscribeToAll();
});

it('raise exception when query not set', function () {
    $this->expectExceptionMessage('Projection query not set');
    $this->context->queries();
});

it('set reactors', function () {
    $reactors = fn () => [];
    $this->context->when($reactors);

    expect($this->context->reactors())->toBe($reactors);
});

it('raise exception when reactors is already set', function () {
    $reactors = fn () => [];
    $this->context->when($reactors);

    $this->expectExceptionMessage('Projection reactors already set');
    $this->context->when($reactors);
});

it('raise exception when reactors not set', function () {
    $this->expectExceptionMessage('Projection reactors not set');
    $this->context->reactors();
});

it('get empty array halt on callbacks', function () {
    expect($this->context->haltOnCallback())->toBe([]);
});

it('set halt on when requested', function (bool $requested) {
    $haltOn = fn (HaltOn $haltOn) => $haltOn->whenRequested($requested);
    $this->context->haltOn($haltOn);

    expect($this->context->haltOnCallback())->toHaveKey('requested');

    $callback = $this->context->haltOnCallback()['requested'];
    expect($callback())->toBe($requested);
})->with([
    'requested' => [true],
    'not requested' => [false],
]);

it('set halt on when signal received', function (array $signals) {
    $haltOn = fn (HaltOn $haltOn) => $haltOn->whenSignalReceived($signals);
    $this->context->haltOn($haltOn);

    expect($this->context->haltOnCallback())->toHaveKey('signalReceived');

    $callback = $this->context->haltOnCallback()['signalReceived'];
    expect($callback())->toBe($signals);
})->with(['signals' => [[1, 2, 3]]]);

it('set halt on when empty event stream', function (?int $expiredAt) {
    $haltOn = fn (HaltOn $haltOn) => $haltOn->whenEmptyEventStream($expiredAt);
    $this->context->haltOn($haltOn);

    expect($this->context->haltOnCallback())->toHaveKey('emptyEventStream');

    $callback = $this->context->haltOnCallback()['emptyEventStream'];
    expect($callback())->toBe($expiredAt);
})->with(['expired at' => [null, 1, 2, 3]]);

it('set halt on when cycle reached', function (int $cycle) {
    $haltOn = fn (HaltOn $haltOn) => $haltOn->whenCycleReached($cycle);
    $this->context->haltOn($haltOn);

    expect($this->context->haltOnCallback())->toHaveKey('cycleReached');

    $callback = $this->context->haltOnCallback()['cycleReached'];
    expect($callback())->toBe($cycle);
})->with(['cycle' => [1, 2, 3]]);

it('set halt on when stream event limit reached', function () {
    $haltOn = fn (HaltOn $haltOn) => $haltOn->whenStreamEventLimitReached(5, false);
    $this->context->haltOn($haltOn);

    expect($this->context->haltOnCallback())->toHaveKey('counterReached');

    $callback = $this->context->haltOnCallback()['counterReached'];
    expect($callback())->toBe([5, false]);
});
