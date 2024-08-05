<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Projector\InMemory\Factory;

use Storm\Contract\Chronicler\Chronicler;
use Storm\Contract\Chronicler\EventStreamProvider;
use Storm\Contract\Chronicler\InMemoryChronicler;
use Storm\Contract\Chronicler\InMemoryQueryFilter;
use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Projector\ProjectionOption;
use Storm\Contract\Projector\ProjectionProvider;
use Storm\Contract\Projector\ProjectorManagerInterface;
use Storm\Contract\Projector\ProjectorMonitorInterface;
use Storm\Contract\Serializer\SymfonySerializer;
use Storm\Projector\Filter\InMemoryFromToPosition;
use Storm\Projector\Options\InMemoryOption;
use Storm\Projector\ProjectorManager;

class InMemoryTestingFactory
{
    public Chronicler|InMemoryChronicler $chronicler;

    public EventStreamProvider $eventStreamProvider;

    public ProjectionProvider $projectionProvider;

    public SystemClock $clock;

    public SymfonySerializer $serializer;

    public ProjectionOption $projectionOption;

    public InMemoryQueryFilter $inMemoryQueryFilter;

    public ?ProjectorManagerInterface $projectorManager = null;

    protected ?ProjectorMonitorInterface $monitor = null;

    public function createProjectorManager(): ProjectorManagerInterface
    {
        if ($this->projectorManager) {
            return $this->projectorManager;
        }

        $this->setupQueryFilter();
        $this->projectionOption = new InMemoryOption();

        $this->projectorManager = new ProjectorManager($this->projectionOption);

        $this->chronicler = $this->projectorManager->getSubscriptionBuilder()->chronicler;
        $this->projectionProvider = $this->projectorManager->getSubscriptionBuilder()->projectionProvider;
        $this->eventStreamProvider = $this->projectorManager->getSubscriptionBuilder()->eventStreamProvider;
        $this->serializer = $this->projectorManager->getSubscriptionBuilder()->serializer;
        $this->clock = $this->projectorManager->getSubscriptionBuilder()->clock;

        return $this->projectorManager;
    }

    public function monitor(): ProjectorMonitorInterface
    {
        return $this->monitor ??= $this->createProjectorManager()->monitor();
    }

    public function setupQueryFilter(): void
    {
        $this->inMemoryQueryFilter ??= new InMemoryFromToPosition();
    }
}
