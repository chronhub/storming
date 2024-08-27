<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow;

use Closure;
use Storm\Contract\Chronicler\QueryFilter;
use Storm\Projector\DefaultContext;
use Storm\Projector\Stream\Query\DiscoverAllStream;
use Storm\Projector\Stream\Query\DiscoverPartition;
use Storm\Projector\Stream\Query\DiscoverStream;
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

    $query = $this->context->query();
    expect($query)->toBeInstanceOf(DiscoverStream::class)
        ->and($query->streams)->toBe(['stream-1']);
});

test('subscribe to partition', function () {
    $this->context->subscribeToPartition('partition-1');

    $query = $this->context->query();
    expect($query)->toBeInstanceOf(DiscoverPartition::class)
        ->and($query->partitions)->toBe(['partition-1']);
});

test('subscribe to all stream', function () {
    $this->context->subscribeToAll();

    $query = $this->context->query();
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

    test('query filter is already set', function () {
        $queryFilter = mock(QueryFilter::class);
        $this->context->withQueryFilter($queryFilter);

        $this->expectExceptionMessage('Projection query filter already set');
        $this->context->withQueryFilter($queryFilter);
    });

    test('query filter is not set', function () {
        $this->expectExceptionMessage('Projection query filter not set');
        $this->context->queryFilter();
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
        $this->context->subscribeToPartition('category-1');

        $this->expectExceptionMessage('Projection query already set');
        $this->context->subscribeToPartition('category-2');
    });

    test('query all stream is already set', function () {
        $this->context->subscribeToAll();

        $this->expectExceptionMessage('Projection query already set');
        $this->context->subscribeToAll();
    });

    test('query not set', function () {
        $this->expectExceptionMessage('Projection query not set');
        $this->context->query();
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

test('set halt on', function (bool $requested) {
    $fn = fn () => $requested;
    $this->context->haltOn(fn (HaltOn $haltOn) => $haltOn->when($fn));

    expect($this->context->haltOnCallback())->not->toBeEmpty()
        ->and($this->context->haltOnCallback()[0])->toBe($fn);
})->with([
    'requested' => [true],
    'not requested' => [false],
]);
