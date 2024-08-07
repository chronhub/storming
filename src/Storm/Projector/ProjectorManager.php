<?php

declare(strict_types=1);

namespace Storm\Projector;

use Storm\Contract\Projector\EmitterProjector;
use Storm\Contract\Projector\ProjectorManagerInterface;
use Storm\Contract\Projector\QueryProjector;
use Storm\Contract\Projector\ReadModel;
use Storm\Contract\Projector\ReadModelProjector;
use Storm\Projector\Connector\ConnectionManager;
use Storm\Projector\Connector\SubscriptionFactoryResolver;
use Storm\Projector\Options\Option;

final readonly class ProjectorManager implements ProjectorManagerInterface
{
    public function __construct(
        private ProjectorServiceManager $manager,
        private SubscriptionFactoryResolver $resolver,
    ) {}

    public function newQueryProjector(array $options = [], ?string $connection = null): QueryProjector
    {
        [$connector, $optionInstance] = $this->getConnectionWithOptions($connection, $options);

        $querySubscription = $this->resolver
            ->resolve('query', $connector)
            ->create(null, null, $optionInstance);

        return new ProjectQuery($querySubscription, new DefaultContext());
    }

    public function newEmitterProjector(string $streamName, array $options = [], ?string $connection = null): EmitterProjector
    {
        [$connector, $optionInstance] = $this->getConnectionWithOptions($connection, $options);

        $emitterSubscription = $this->resolver
            ->resolve('emitter', $connector)
            ->create($streamName, null, $optionInstance);

        return new ProjectEmitter($emitterSubscription, new DefaultContext(), $streamName);
    }

    public function newReadModelProjector(string $streamName, ReadModel $readModel, array $options = [], ?string $connection = null): ReadModelProjector
    {
        [$connector, $optionInstance] = $this->getConnectionWithOptions($connection, $options);

        $readModelSubscription = $this->resolver
            ->resolve('read_model', $connector)
            ->create($streamName, $readModel, $optionInstance);

        return new ProjectReadModel($readModelSubscription, new DefaultContext(), $streamName);
    }

    /**
     * @return array{ConnectionManager, Option}
     */
    private function getConnectionWithOptions(?string $connection = null, array $options = []): array
    {
        $connector = $this->manager->connection($connection);

        $option = $connector->toProjectionOption($options);

        return [$connector, $option];
    }
}
