<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector;

use Filter\ProjectionQueryFilter;
use Storm\Contract\Chronicler\QueryFilter;
use Storm\Contract\Projector\ContextReader;
use Storm\Contract\Projector\QuerySubscriber;
use Storm\Projector\ProjectQuery;

beforeEach(function () {
    $this->subscriber = mock(QuerySubscriber::class);
    $this->context = mock(ContextReader::class);
    $this->projection = new ProjectQuery($this->subscriber, $this->context);
});

test('start projection', function (bool $runInBackground) {
    $this->context->expects('id')->andReturn('projection-id');
    $this->subscriber->expects('start')->with($this->context, $runInBackground);

    $this->projection->run($runInBackground);
})->with('keep projection running');

test('set query filter', function (QueryFilter $queryFilter) {
    $this->context->expects('withQueryFilter')->with($queryFilter);

    $return = $this->projection->filter($queryFilter);
    expect($return)->toBe($this->projection);
})->with([
    'query filter' => fn () => mock(QueryFilter::class),
    'projection filter' => fn () => mock(ProjectionQueryFilter::class),
]);

test('keep state', function () {
    $this->context->expects('withKeepState');

    expect($this->projection->keepState())->toBe($this->projection);
});

test('resets projection', function () {
    $this->subscriber->expects('resets');

    $this->projection->reset();
});
