<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow;

/**
 * @mixin Component
 */
class Process
{
    public function __construct(protected readonly ComponentRegistry $component) {}

    public function dispatch(string|object $event, mixed ...$arguments): void
    {
        $this->component->dispatcher()->notify($event, ...$arguments);
    }

    public function addListener(string $event, string|callable|array $handler): void
    {
        $this->component->dispatcher()->listenTo($event, $handler);
    }

    public function removeListener(string $event): void
    {
        $this->component->dispatcher()->forgetListener($event);
    }

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
