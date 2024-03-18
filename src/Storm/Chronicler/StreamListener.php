<?php

declare(strict_types=1);

namespace Storm\Chronicler;

use Closure;
use ReflectionFunction;
use Storm\Contract\Tracker\Listener;

final class StreamListener implements Listener
{
    /**
     * @var callable
     */
    private $story;

    public function __construct(
        private readonly string $event,
        callable $callback,
        private readonly int $priority = 0
    ) {
        $this->story = $callback;
    }

    public function name(): string
    {
        return $this->event;
    }

    public function priority(): int
    {
        return $this->priority;
    }

    public function story(): callable
    {
        return $this->story;
    }

    public function origin(): string
    {
        if ($this->story instanceof Closure) {
            $origin = new ReflectionFunction($this->story);

            return $origin->getClosureScopeClass()->name;
        }

        return $this->story::class;
    }
}
