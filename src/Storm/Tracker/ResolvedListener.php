<?php

declare(strict_types=1);

namespace Storm\Tracker;

use ReflectionFunction;
use Storm\Contract\Tracker\Listener;

/**
 * @property-read object $subscriber
 */
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

    public function origin(): ?string
    {
        $ref = new ReflectionFunction($this->instance);

        if ($ref->isClosure()) {
            $scopeClass = $ref->getClosureScopeClass();

            return $scopeClass->getName() ?? null;
        }

        return $this->instance::class;
    }
}
