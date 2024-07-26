<?php

declare(strict_types=1);

namespace Storm\Tests\Datasets;

use Storm\Projector\ProjectionStatus;

dataset('projection status', ProjectionStatus::cases());

dataset('projection status as strings', ProjectionStatus::strings());

dataset('keep projection running', [
    'keep running' => [true],
    'do not keep running' => [false],
]);

dataset('delete projection with emitted events', [
    'with emitted events' => [true],
    'without emitted events' => [false],
]);

dataset('projection exists', [
    'projection exists' => [true],
    'projection does not exist' => [false],
]);
