<?php

declare(strict_types=1);

namespace Storm\Projector\Factory\Component;

use BadMethodCallException;
use Storm\Contract\Projector\ContextReader;
use Storm\Projector\Workflow\Process;

final readonly class Components implements ComponentManager
{
    public function __construct(
        public array $components
    ) {}

    public function call(callable $callback): mixed
    {
        return $callback($this);
    }

    public function subscribe(Process $process, ContextReader $context): void
    {
        foreach ($this->components as $component) {
            if ($component instanceof ComponentSubscriber) {
                $component->subscribe($process, $context);
            }
        }
    }

    public function __call(string $name, array $arguments): object
    {
        if (! isset($this->components[$name])) {
            throw new BadMethodCallException("Component $name not found");
        }

        return $this->components[$name];
    }
}
