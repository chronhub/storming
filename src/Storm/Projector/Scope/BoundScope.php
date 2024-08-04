<?php

declare(strict_types=1);

namespace Storm\Projector\Scope;

use Closure;
use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Message\DomainEvent;

trait BoundScope
{
    protected ?DomainEvent $event = null;

    public function event(): ?DomainEvent
    {
        return $this->event;
    }

    public function eventClass(): ?string
    {
        return $this?->event::class;
    }

    public function userState(): ?UserStateScope
    {
        return $this->userState;
    }

    public function streamName(): string
    {
        return $this->process->stream()->get();
    }

    public function clock(): SystemClock
    {
        return $this->clock;
    }

    /**
     * @internal
     */
    public function __invoke(?DomainEvent $event, ?UserStateScope $userState = null): static
    {
        $this->event = $event;
        $this->userState = $userState;

        return $this;
    }

    public function match(string $event): bool
    {
        return $event === $this->event::class;
    }

    /**
     * @param Closure(self): bool $callback
     */
    public function stopWhen(Closure $callback): void
    {
        if ($callback($this) === true) {
            $this->stop();
        }
    }
}
