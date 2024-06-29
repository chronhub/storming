<?php

declare(strict_types=1);

namespace Storm\Tests\Datasets;

use Storm\Projector\ProjectionStatus;

dataset('projection status', ProjectionStatus::cases());

dataset('projection status as strings', ProjectionStatus::strings());
