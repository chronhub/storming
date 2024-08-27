<?php

declare(strict_types=1);

namespace Storm\Projector\Scope;

use Storm\Contract\Message\DomainEvent;

interface EmitterScope extends PersistentProjectorScope
{
    /**
     * Append event to a (new) stream under the current projection name.
     */
    public function emit(DomainEvent $event): void;

    /**
     * Append event to a (new) stream with the given stream name.
     */
    public function linkTo(string $streamName, DomainEvent $event): void;
}
