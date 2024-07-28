<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow;

use Storm\Projector\Workflow\HaltOn;

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
    expect($this->haltOn->callbacks())->toBeNull();
});

test('set requested callback', function (bool $requested) {
    $instance = $this->haltOn->when(fn () => $requested);
    expect($instance)->toBeInstanceOf(HaltOn::class);

    $callback = $instance->callbacks();

    expect($callback())->toBe($requested);
})->with([
    ['requested' => true],
    ['not requested' => false],
]);
