<?php

declare(strict_types=1);

namespace Storm\Projector;

use Storm\Contract\Projector\ConnectorResolver;
use Storm\Contract\Projector\EmitterProjector;
use Storm\Contract\Projector\ProjectorManagerInterface;
use Storm\Contract\Projector\QueryProjector;
use Storm\Contract\Projector\ReadModel;
use Storm\Contract\Projector\ReadModelProjector;
use Storm\Projector\Connector\ConnectionManager;
use Storm\Projector\Factory\ProviderResolverFactory;
use Storm\Projector\Options\Option;

final readonly class ProjectorManager implements ProjectorManagerInterface
{
    public function __construct(
        private ConnectorResolver $connectorResolver,
        private ProviderResolverFactory $resolverFactory,
    ) {}

    public function newQueryProjector(array $options = [], ?string $connection = null): QueryProjector
    {
        [$connector, $optionInstance] = $this->getConnectionWithOptions($connection, $options);

        $queryManager = $this->resolverFactory
            ->resolve('query', $connector)
            ->create(null, null, $optionInstance);

        return new ProjectQuery($queryManager, new DefaultContext());
    }

    public function newEmitterProjector(string $streamName, array $options = [], ?string $connection = null): EmitterProjector
    {
        [$connector, $optionInstance] = $this->getConnectionWithOptions($connection, $options);

        $emitterManager = $this->resolverFactory
            ->resolve('emitter', $connector)
            ->create($streamName, null, $optionInstance);

        return new ProjectEmitter($emitterManager, new DefaultContext(), $streamName);
    }

    public function newReadModelProjector(string $streamName, ReadModel $readModel, array $options = [], ?string $connection = null): ReadModelProjector
    {
        [$connector, $optionInstance] = $this->getConnectionWithOptions($connection, $options);

        $readModelManager = $this->resolverFactory
            ->resolve('read_model', $connector)
            ->create($streamName, $readModel, $optionInstance);

        return new ProjectReadModel($readModelManager, new DefaultContext(), $streamName);
    }

    /**
     * @return array{ConnectionManager, Option}
     */
    private function getConnectionWithOptions(?string $connection = null, array $options = []): array
    {
        $connector = $this->connectorResolver->connection($connection);

        $option = $connector->toOption($options);

        return [$connector, $option];
    }
}
