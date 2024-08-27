<?php

declare(strict_types=1);

namespace Storm\Tests\Datasets;

use Storm\Projector\ProjectionStatus;

dataset('projection status', ProjectionStatus::cases());

dataset('projection status as strings', ProjectionStatus::strings());

dataset('keep projection running', [
    'should run in background' => [true],
    'should run once' => [false],
]);

dataset('delete projection with emitted events', [
    'with emitted events' => [true],
    'without emitted events' => [false],
]);

dataset('projection exists', [
    'should exists' => [true],
    'should not exists' => [false],
]);

dataset('projection optional description id', [null, 'describe the projection with a custom id']);

/**
 * The retries are used to configure checkpoint recovery,
 * a retry represents a sleep duration in milliseconds
 *
 * Max attempts: 10
 */
dataset('projection options with non empty retries', [
    'two retries' => [[1, 2]],
    'five retries' => [[1, 2, 3, 4, 5]],
    'ten retries' => [[1, 2, 3, 4, 5, 6, 7, 8, 9, 10]],
]);

dataset('projection options record gap', [
    ['should record gap' => true],
    ['should not record gap' => false],
]);
