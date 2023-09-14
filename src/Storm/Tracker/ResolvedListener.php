<?php

declare(strict_types=1);

namespace Storm\Tracker;

use Storm\Contract\Tracker\Listener;

/**
 * @property-read object $subscriber
 */
final readonly class ResolvedListener implements Listener
{
    public function __construct(
        public object $subscriber,
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
        return ($this->subscriber)();
    }

    public function origin(): ?string
    {
        return $this->subscriber::class;
    }
}
