<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow;

use Storm\Contract\Projector\Component;

/**
 * @mixin Component
 */
class Process
{
    public function __construct(
        protected readonly Component $component,
    ) {}

    public function dispatch(string|object $event): void
    {
        $this->component->dispatcher()->emit($event);
    }

    public function addListener(string $event, string|callable|array $handler): void
    {
        $this->component->dispatcher()->listenTo($event, $handler);
    }

    public function removeListener(string $event): void
    {
        $this->component->dispatcher()->forgetListener($event);
    }

    // fixMe add common component methods

    public function isSprintTerminated(): bool
    {
        return ! $this->component->sprint()->inBackground()
            || ! $this->component->sprint()->inProgress();
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->component->{$name}(...$arguments);
    }
}
