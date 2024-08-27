<?php

declare(strict_types=1);

namespace Storm\Projector\Scope;

use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Message\DomainEvent;

/**
 * @phpstan-require-implements ProjectorScope
 */
trait ScopeAccess
{
    protected ?DomainEvent $event = null;

    public function event(): ?DomainEvent
    {
        return $this->event;
    }

    public function userState(): ?UserState
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
     * checkMe it's sensitive to use this method
     *  either encapsulate again or use reflection
     */
    public function __invoke(?DomainEvent $event, ?UserState $userState = null): static
    {
        $this->event = $event;
        $this->userState = $userState;

        return $this;
    }
}
