<?php

declare(strict_types=1);

namespace Storm\Tracker;

use Closure;
use ReflectionFunction;
use Storm\Contract\Tracker\Listener;

final readonly class ResolvedListener implements Listener
{
    public function __construct(
        public object $instance,
        private string $name,
        private int $priority
    ) {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function priority(): int
    {
        return $this->priority;
    }

    public function story(): callable
    {
        return ($this->instance)();
    }

    public function origin(): string
    {
        if ($this->instance instanceof Closure) {
            $ref = new ReflectionFunction($this->instance);

            return $ref->getClosureScopeClass()->getName();
        }

        return $this->instance::class;
    }
}
