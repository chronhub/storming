<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification;

use Storm\Contract\Projector\EmitOnce;

/**
 * Emitted before the workflow renewal and reset.
 */
final class BeforeWorkflowRenewal implements EmitOnce {}
