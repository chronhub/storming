<?php

declare(strict_types=1);

namespace Storm\Projector\Support;

use Illuminate\Support\Fluent;

/**
 * @property-read string $projection_id
 * @property-read int $started_at
 * @property-read int $elapsed_time
 * @property-read int $ended_at
 * @property-read int $cycle
 * @property-read int $acked_event
 * @property-read int $total_event
 * @property-read array $checkpoint
 * @property-read array $options
 */
class ProjectionReport extends Fluent {}
