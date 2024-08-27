<?php

declare(strict_types=1);

namespace Storm\Chronicler;

use Storm\Contract\Chronicler\Chronicler;
use Storm\Contract\Chronicler\ChroniclerManager;

class EventStoreBridge
{
    public function __construct(protected ChroniclerManager $manager) {}

    public function getEventStore(string $name): Chronicler
    {
        return $this->manager->create($name);
    }
}
