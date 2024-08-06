<?php

declare(strict_types=1);

namespace Storm\Projector\Provider;

use Storm\Contract\Message\DomainEvent;

interface EmitterProvider extends PersistentProvider
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
