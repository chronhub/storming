<?php

declare(strict_types=1);

namespace Storm\Projector\Scope;

use Storm\Contract\Message\DomainEvent;

interface ProjectorScopeFactory
{
    /**
     * Process the event and return the projector scope
     */
    public function handle(DomainEvent $event, ?array $userState = null): ProjectorScope;
}
