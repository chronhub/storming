<?php

declare(strict_types=1);

namespace Storm\Chronicler\Tracker;

final class StreamListener implements Listener
{
    /** @var callable */
    private $callback;

    public function __construct(
        public readonly string $eventName,
        callable $callback,
        public readonly int $eventPriority
    ) {
        $this->callback = $callback;
    }

    public function name(): string
    {
        return $this->eventName;
    }

    public function priority(): int
    {
        return $this->eventPriority;
    }

    public function callback(): callable
    {
        return $this->callback;
    }
}
