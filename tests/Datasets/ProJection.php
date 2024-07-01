<?php

declare(strict_types=1);

namespace Storm\Tests\Datasets;

use Storm\Projector\ProjectionStatus;

dataset('projection status', ProjectionStatus::cases());

dataset('projection status as strings', ProjectionStatus::strings());

dataset('keep projection running', [
    'keep running' => [true],
    'run once' => [false],
]);

dataset('delete projection with emitted events', [
    'delete with emitted events' => [true],
    'delete without emitted events' => [false],
]);
