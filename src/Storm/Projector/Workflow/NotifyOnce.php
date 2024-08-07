<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow;

/**
 * Marker interface for events that should only be emitted once,
 * during a workflow cycle.
 */
interface NotifyOnce {}
