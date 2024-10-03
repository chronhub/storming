<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow;

use Storm\Projector\Factory\Component\ComponentManager;
use Storm\Projector\Factory\Component\Components;

/**
 * @mixin Components
 */
readonly class Process
{
    public function __construct(
        protected ComponentManager $components
    ) {}

    public function dispatch(string|object $event, mixed ...$arguments): void
    {
        $this->components->dispatcher()->notify($event, ...$arguments);
    }

    public function addListener(string $event, string|callable|array $handler): void
    {
        $this->components->dispatcher()->listenTo($event, $handler);
    }

    public function removeListener(string $event): void
    {
        $this->components->dispatcher()->forgetListener($event);
    }

    public function isSprintTerminated(): bool
    {
        return ! $this->components->sprint()->inBackground()
            || ! $this->components->sprint()->inProgress();
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->components->{$name}(...$arguments);
    }
}
