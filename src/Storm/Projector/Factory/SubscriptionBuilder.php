<?php

declare(strict_types=1);

namespace Storm\Projector\Factory;

use Storm\Contract\Chronicler\Chronicler;
use Storm\Contract\Chronicler\ChroniclerDecorator;
use Storm\Contract\Chronicler\EventStreamProvider;
use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Projector\Management;
use Storm\Contract\Projector\ProjectionOption;
use Storm\Contract\Projector\ProjectionProvider;
use Storm\Contract\Projector\Repository;
use Storm\Contract\Serializer\SymfonySerializer;
use Storm\Projector\Repository\GenericRepository;
use Storm\Projector\Repository\LockManager;
use Storm\Projector\Subscription\ManagementEventMap;
use Storm\Projector\Workflow\Component;
use Storm\Projector\Workflow\Process;

readonly class SubscriptionBuilder
{
    public Chronicler $chronicler;

    public function __construct(
        Chronicler $chronicler,
        public ProjectionProvider $projectionProvider,
        public EventStreamProvider $eventStreamProvider,
        public SystemClock $clock,
        public SymfonySerializer $serializer,
    ) {
        while ($chronicler instanceof ChroniclerDecorator) {
            $chronicler = $chronicler->innerChronicler();
        }

        $this->chronicler = $chronicler;
    }

    public function createRepository(string $streamName, ProjectionOption $options): Repository
    {
        return new GenericRepository(
            $this->projectionProvider,
            $this->createLockManager($options),
            $this->serializer,
            $streamName
        );
    }

    public function createProcessManager(ProjectionOption $options): Process
    {
        $component = new Component($options, $this->eventStreamProvider, $this->clock);

        return new Process($component);
    }

    public function createLockManager(ProjectionOption $options): LockManager
    {
        return new LockManager($this->clock, $options->getTimeout(), $options->getLockout());
    }

    public function subscribeToMap(Management $management, Process $process): void
    {
        $map = new ManagementEventMap();

        $map->subscribeTo($management, $process);
    }
}
