<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector;

use Storm\Contract\Chronicler\QueryFilter;
use Storm\Contract\Projector\ContextReader;
use Storm\Contract\Projector\ProjectionQueryFilter;
use Storm\Contract\Projector\QuerySubscriber;
use Storm\Projector\ProjectQuery;

beforeEach(function () {
    $this->subscriber = mock(QuerySubscriber::class);
    $this->context = mock(ContextReader::class);
    $this->projection = new ProjectQuery($this->subscriber, $this->context);
});

test('start projection', function (bool $runInBackground) {
    $this->context->shouldReceive('id')->andReturn('projection-id')->once();
    $this->subscriber->shouldReceive('start')->with($this->context, $runInBackground)->once();
    $this->projection->run($runInBackground);
})->with([
    'keep running' => [true],
    'run once' => [false],
]);

test('set filter', function (QueryFilter $queryFilter) {
    $this->context->shouldReceive('withQueryFilter')->with($queryFilter)->once();

    $return = $this->projection->filter($queryFilter);

    expect($return)->toBe($this->projection);
})->with([
    'query filter' => fn () => mock(QueryFilter::class),
    'projection filter' => fn () => mock(ProjectionQueryFilter::class),
]);

test('keep state', function () {
    $this->context->shouldReceive('withKeepState')->once();

    $return = $this->projection->keepState();

    expect($return)->toBe($this->projection);
});
