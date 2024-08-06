<?php

declare(strict_types=1);

namespace Storm\Projector;

use Storm\Contract\Projector\EmitterProjector;
use Storm\Contract\Projector\ProjectorManagerInterface;
use Storm\Contract\Projector\ProjectorMonitorInterface;
use Storm\Contract\Projector\QueryProjector;
use Storm\Contract\Projector\ReadModel;
use Storm\Contract\Projector\ReadModelProjector;
use Storm\Projector\Connector\SubscriptionFactoryResolver;
use Storm\Projector\Workflow\DefaultContext;

final class ProjectorManager implements ProjectorManagerInterface
{
    /** @var array<string, ProjectorMonitorInterface>|array */
    private array $monitors = [];

    public function __construct(
        private readonly ProjectorServiceManager $manager,
        private readonly SubscriptionFactoryResolver $resolver,
    ) {}

    public function newQueryProjector(array $options = [], ?string $connection = null): QueryProjector
    {
        $subscriber = $this->manager->connection($connection);

        $options = $subscriber->getProjectionOption($options);

        $querySubscription = $this->resolver
            ->resolve('query', $subscriber)
            ->create(null, null, $options);

        return new ProjectQuery($querySubscription, new DefaultContext());
    }

    public function newEmitterProjector(string $streamName, array $options = [], ?string $connection = null): EmitterProjector
    {
        $subscriber = $this->manager->connection($connection);

        $options = $subscriber->getProjectionOption($options);

        $emitterSubscription = $this->resolver
            ->resolve('emitter', $subscriber)
            ->create($streamName, null, $options);

        return new ProjectEmitter($emitterSubscription, new DefaultContext(), $streamName);
    }

    public function newReadModelProjector(string $streamName, ReadModel $readModel, array $options = [], ?string $connection = null): ReadModelProjector
    {
        $subscriber = $this->manager->connection($connection);

        $options = $subscriber->getProjectionOption($options);

        $readModelSubscription = $this->resolver
            ->resolve('read_model', $subscriber)
            ->create($streamName, $readModel, $options);

        return new ProjectReadModel($readModelSubscription, new DefaultContext(), $streamName);
    }

    public function monitor(?string $connection = null): ProjectorMonitorInterface
    {
        if (isset($this->monitors[$connection])) {
            return $this->monitors[$connection];
        }

        $manager = $this->manager->connection($connection);

        return $this->monitors[$manager->getConnection()] = new ProjectorMonitor(
            $manager->projectionProvider(),
            $manager->serializer(),
        );
    }
}
