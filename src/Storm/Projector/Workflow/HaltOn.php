<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow;

use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Workflow\Notification\ShouldTerminateWorkflow;

/**
 * Stop the projector with a given callback.
 *
 * It can only stop after a cycle is completed.
 *
 * {@see ShouldTerminateWorkflow}
 *
 * It can be used to stop the projector after a given number of cycles or a given timestamp.
 * But when dealing with counters, you cannot stop with a strict comparison;
 * but gt/lt/gte/lte as we do not have access to live counters.
 * Otherwise, you should use a projector scope in your reactors to check the counter-value against the user state.
 */
class HaltOn
{
    /**
     * @var callable(NotificationHub): bool
     */
    protected $callback = null;

    /**
     * Stop the projector when the given callback returns true.
     *
     * @param  callable(NotificationHub): bool $callback
     * @return $this
     */
    public function when(callable $callback): self
    {
        // todo we should allow chaining and merge halton
        $this->callback = $callback;

        return $this;
    }

    public function callback(): ?callable
    {
        return $this->callback;
    }
}
