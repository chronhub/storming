<?php

declare(strict_types=1);

namespace Storm\Projector;

use Storm\Contract\Projector\EmitterProjector;
use Storm\Contract\Projector\ProjectorManager;
use Storm\Contract\Projector\ProjectorMonitor;
use Storm\Contract\Projector\QueryProjector;
use Storm\Contract\Projector\ReadModel;
use Storm\Contract\Projector\ReadModelProjector;
use Storm\Projector\Connector\ConnectionManager;
use Storm\Projector\Connector\ConnectorManager;
use Storm\Projector\Factory\ProviderFactoryRegistry;
use Storm\Projector\Options\Option;

final class ManageProjector implements ProjectorManager
{
    /** @var array<string, ProjectorMonitor>|array */
    private array $monitors = [];

    public function __construct(
        private readonly ConnectorManager $connectorResolver,
        private readonly ProviderFactoryRegistry $provider,
    ) {}

    public function query(array $options = [], ?string $connection = null): QueryProjector
    {
        [$connector, $optionInstance] = $this->handleConnectionWithMergedOptions($connection, $options);

        $manager = $this->provider
            ->resolve('query', $connector)
            ->create(null, null, $optionInstance);

        return new ProjectQuery($manager, new DefaultContext);
    }

    public function emitter(string $streamName, array $options = [], ?string $connection = null): EmitterProjector
    {
        [$connector, $optionInstance] = $this->handleConnectionWithMergedOptions($connection, $options);

        $manager = $this->provider
            ->resolve('emitter', $connector)
            ->create($streamName, null, $optionInstance);

        return new ProjectEmitter($manager, new DefaultContext, $streamName);
    }

    public function readModel(string $streamName, ReadModel $readModel, array $options = [], ?string $connection = null): ReadModelProjector
    {
        // todo allow string read model
        //  either constructed connection or setter
        [$connector, $optionInstance] = $this->handleConnectionWithMergedOptions($connection, $options);

        $manager = $this->provider
            ->resolve('read_model', $connector)
            ->create($streamName, $readModel, $optionInstance);

        return new ProjectReadModel($manager, new DefaultContext, $streamName);
    }

    public function monitor(?string $connection = null): ProjectorMonitor
    {
        $connectionManager = $this->connectorResolver->connection($connection);

        if (! $connection) {
            $connection = $this->connectorResolver->getDefaultDriver();
        }

        return $this->monitors[$connection] ??= new Monitor(
            $connectionManager->projectionProvider(),
            $connectionManager->serializer()
        );
    }

    /**
     * @return array{ConnectionManager, Option}
     */
    private function handleConnectionWithMergedOptions(?string $connection = null, array $options = []): array
    {
        $connector = $this->connectorResolver->connection($connection);

        $option = $connector->toOption($options);

        return [$connector, $option];
    }
}
