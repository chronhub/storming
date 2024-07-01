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

test('initialize context', function () {
    $this->context->initialize(fn () => []);

    expect($this->context->userState())->toBeInstanceOf(Closure::class);
});

test('initialize the context with query filter', function () {
    $queryFilter = mock(QueryFilter::class);
    $this->context->withQueryFilter($queryFilter);

    expect($this->context->queryFilter())->toBe($queryFilter);
});

test('initialize the context with keep state', function () {
    $this->context->withKeepState();

    expect($this->context->keepState())->toBeTrue();
});

test('set id', function () {
    $this->context->withId('id');

    expect($this->context->id())->toBe('id');
});

test('subscribe to stream', function () {
    $this->context->subscribeToStream('stream-1');

    $query = $this->context->queries();
    expect($query)->toBeInstanceOf(DiscoverStream::class)
        ->and($query->streams)->toBe(['stream-1']);
});

test('subscribe to category', function () {
    $this->context->subscribeToCategory('category-1');

    $query = $this->context->queries();
    expect($query)->toBeInstanceOf(DiscoverCategories::class)
        ->and($query->categories)->toBe(['category-1']);
});

test('subscribe to all stream', function () {
    $this->context->subscribeToAll();

    $query = $this->context->queries();
    expect($query)->toBeInstanceOf(DiscoverAllStream::class);
});

test('set reactors', function () {
    $reactors = fn () => [];
    $this->context->when($reactors);

    expect($this->context->reactors())->toBe($reactors);
});

test('get empty array halt on callbacks with default instance', function () {
    expect($this->context->haltOnCallback())->toBe([]);
});

describe('raise exception when', function () {
    test('context is already initialized', function () {
        $this->context->initialize(fn () => []);

        $this->expectExceptionMessage('Projection already initialized');
        $this->context->initialize(fn () => []);
    });

    test('when query filter is already set', function () {
        $queryFilter = mock(QueryFilter::class);
        $this->context->withQueryFilter($queryFilter);

        $this->expectExceptionMessage('Projection query filter already set');
        $this->context->withQueryFilter($queryFilter);
    });

    test('keep state is already set', function () {
        $this->context->withKeepState();

        $this->expectExceptionMessage('Projection keep state already set');
        $this->context->withKeepState();
    });

    test('id is already set', function () {
        $this->context->withId('id');

        $this->expectExceptionMessage('Projection id already set');
        $this->context->withId('id');
    });

    test('query stream is already set', function () {
        $this->context->subscribeToStream('stream-1');

        $this->expectExceptionMessage('Projection query already set');
        $this->context->subscribeToStream('stream-2');
    });

    test('query categories is already set', function () {
        $this->context->subscribeToCategory('category-1');

        $this->expectExceptionMessage('Projection query already set');
        $this->context->subscribeToCategory('category-2');
    });

    test('query all stream is already set', function () {
        $this->context->subscribeToAll();

        $this->expectExceptionMessage('Projection query already set');
        $this->context->subscribeToAll();
    });

    test('query not set', function () {
        $this->expectExceptionMessage('Projection query not set');
        $this->context->queries();
    });

    test('reactors is already set', function () {
        $reactors = fn () => [];
        $this->context->when($reactors);

        $this->expectExceptionMessage('Projection reactors already set');
        $this->context->when($reactors);
    });

    test('reactors not set', function () {
        $this->expectExceptionMessage('Projection reactors not set');
        $this->context->reactors();
    });
});

describe('set halt on when', function () {
    test('requested', function (bool $requested) {
        $haltOn = fn (HaltOn $haltOn) => $haltOn->whenRequested($requested);
        $this->context->haltOn($haltOn);

        expect($this->context->haltOnCallback())->toHaveKey('requested');

        $callback = $this->context->haltOnCallback()['requested'];
        expect($callback())->toBe($requested);
    })->with([
        'requested' => [true],
        'not requested' => [false],
    ]);

    test('signal received', function (array $signals) {
        $haltOn = fn (HaltOn $haltOn) => $haltOn->whenSignalReceived($signals);
        $this->context->haltOn($haltOn);

        expect($this->context->haltOnCallback())->toHaveKey('signalReceived');

        $callback = $this->context->haltOnCallback()['signalReceived'];
        expect($callback())->toBe($signals);
    })->with([
        'one signal' => [['SIGINT']],
        'many signals' => [['SIGINT', 'SIGTERM']],
    ]);

    test('empty event stream', function (?int $expiredAt) {
        $haltOn = fn (HaltOn $haltOn) => $haltOn->whenEmptyEventStream($expiredAt);
        $this->context->haltOn($haltOn);

        expect($this->context->haltOnCallback())->toHaveKey('emptyEventStream');

        $callback = $this->context->haltOnCallback()['emptyEventStream'];
        expect($callback())->toBe($expiredAt);
    })->with([
        ['null expired' => null],
        ['expired at 1' => 1],
        ['expired at 10' => 10],
    ]);

    test('cycle reached', function (int $cycle) {
        $haltOn = fn (HaltOn $haltOn) => $haltOn->whenCycleReached($cycle);
        $this->context->haltOn($haltOn);

        expect($this->context->haltOnCallback())->toHaveKey('cycleReached');

        $callback = $this->context->haltOnCallback()['cycleReached'];
        expect($callback())->toBe($cycle);
    })->with([
        ['one cycle' => 1],
        ['two cycles' => 2],
        ['three cycles' => 3],
    ]);

    test('stream event limit reached', function (int $limit, bool $resetCounterOnStop) {
        $haltOn = fn (HaltOn $haltOn) => $haltOn->whenStreamEventLimitReached($limit, $resetCounterOnStop);
        $this->context->haltOn($haltOn);

        expect($this->context->haltOnCallback())->toHaveKey('counterReached');

        $callback = $this->context->haltOnCallback()['counterReached'];
        expect($callback())->toBe([$limit, $resetCounterOnStop]);
    })->with([
        ['limit of 1' => 1],
        ['limit of 10' => 10],
        ['limit of 50' => 50],
    ])->with([
        'reset counter on stop' => [true],
        'do not reset counter on stop' => [false],
    ]);
});
