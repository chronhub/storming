<?php

declare(strict_types=1);

namespace Storm\Tracker;

use ReflectionFunction;
use Storm\Contract\Tracker\EventListener;

final class GenericEventListener implements EventListener
{
    /**
     * @var callable
     */
    private $story;

    public function __construct(
        private readonly string $name,
        callable $story,
        private readonly int $priority
    ) {
        $this->story = $story;
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
        return $this->story;
    }

    public function origin(): ?string
    {
        $origin = new ReflectionFunction($this->story);

        return $origin->getClosureScopeClass()?->name;
    }
}
