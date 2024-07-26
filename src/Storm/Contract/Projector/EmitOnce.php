<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

/**
 * Marker interface for events that should only be emitted once,
 * during a workflow cycle.
 */
interface EmitOnce {}
