<?php

declare(strict_types=1);

namespace Storm\Projector\Projection\Events;

use Storm\Projector\Workflow\NotifyOnce;

final class ProjectionLockUpdated implements NotifyOnce {}
