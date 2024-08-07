<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Filter;

use InvalidArgumentException;
use RuntimeException;
use Storm\Projector\Stream\Filter\LoadLimiter;

test('default instance', function (int $value) {
    $filter = new LoadLimiter($value);

    expect($filter->value)->toBe($value);
})->with([[1], [2], [3], [4], [5], [6], [7], [8], [9], [10]]);

test('instance with zero value transformed to php int max', function () {
    $filter = new LoadLimiter(0);

    expect($filter->value)->toBe(PHP_INT_MAX);
});

test('instance with negative value raises exception', function (int $value) {
    new LoadLimiter($value);

})
    ->with([[-1], [-5], [-50]])
    ->throws(InvalidArgumentException::class, 'LoadLimiter value must be greater than or equal to 0');

test('max position', function (int $value, int $position, int $expected) {
    $filter = new LoadLimiter($value);

    expect($filter->maxPosition($position))->toBe($expected);
})->with([[PHP_INT_MAX - 1, 1, PHP_INT_MAX], [1, 1, 2], [1, 2, 3], [5, 10, 15]]);

test('max position with load limiter with php int max returns the same value', function (int $loadLimiter, int $position, int $expected) {
    $filter = new LoadLimiter($loadLimiter);

    expect($filter->maxPosition($position))->toBe($expected);
})->with([[0, 1, PHP_INT_MAX], [0, 100, PHP_INT_MAX], [0, 10000, PHP_INT_MAX]]);

test('max position with load limiter with php int max and position greater than php int max raises exception', function (int $loadLimiter, int $position) {
    $filter = new LoadLimiter($loadLimiter);

    $filter->maxPosition($position);
})
    ->with([[PHP_INT_MAX - 2, 3], [PHP_INT_MAX - 10, 11], [PHP_INT_MAX - 100, 101]])
    ->throws(RuntimeException::class, 'LoadLimiter value + given position is greater than PHP_INT_MAX');
