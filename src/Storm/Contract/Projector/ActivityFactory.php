<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

interface ActivityFactory
{
    /**
     * @return array<callable>
     */
    public function __invoke(Subscriptor $subscriptor, ProjectorScope $projectorScope): array;
}
