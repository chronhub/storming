<?php

declare(strict_types=1);

namespace Storm\Projector;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;
use Storm\Contract\Chronicler\DatabaseChronicler;
use Storm\Contract\Projector\EmitterProjector;
use Storm\Contract\Projector\ProjectorManager;
use Storm\Contract\Projector\ProjectorMonitor;
use Storm\Contract\Projector\QueryProjector;
use Storm\Contract\Projector\ReadModel;
use Storm\Contract\Projector\ReadModelProjector;
use Storm\Projector\Connector\ConnectionManager;
use Storm\Projector\Connector\ConnectorManager;
use Storm\Projector\Factory\Resolver;
use Storm\Projector\Options\Option;

use function is_string;

final class ManageProjector implements ProjectorManager
{
    /** @var array<string, ProjectorMonitor>|array */
    private array $monitors = [];

    public function __construct(
        private readonly Container $container,
        private readonly ConnectorManager $connectorResolver,
        private readonly Resolver $resolver,
    ) {}

    public function query(array $options = [], ?string $connection = null, ?string $name = null): QueryProjector
    {
        [$connector, $optionInstance] = $this->handleConnectionWithMergedOptions($connection, $options);

        $manager = $this->resolver
            ->resolve($name ?? 'query', $connector)
            ->create(null, null, $optionInstance);

        return new ProjectQuery($manager, new DefaultContext);
    }

    public function emitter(string $streamName, array $options = [], ?string $connection = null, ?string $name = null): EmitterProjector
    {
        [$connector, $optionInstance] = $this->handleConnectionWithMergedOptions($connection, $options);

        $manager = $this->resolver
            ->resolve($name ?? 'emitter', $connector)
            ->create($streamName, null, $optionInstance);

        return new ProjectEmitter($manager, new DefaultContext, $streamName);
    }

    public function readModel(string $streamName, string|ReadModel $readModel, array $options = [], ?string $connection = null, ?string $name = null): ReadModelProjector
    {
        [$connector, $optionInstance] = $this->handleConnectionWithMergedOptions($connection, $options);

        if (is_string($readModel)) {
            $readModel = $this->configureReadModel($connector, $readModel);
        }

        $manager = $this->resolver
            ->resolve($name ?? 'read_model', $connector)
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

    /**
     * Configure the read model passed as string.
     *
     * @throws BindingResolutionException
     */
    private function configureReadModel(ConnectionManager $manager, string $readModel): ReadModel
    {
        if ($this->container->bound($readModel)) {
            return $this->container[$readModel];
        }

        $eventStore = $manager->eventStore();
        if ($eventStore instanceof DatabaseChronicler) {
            $connection = $eventStore->getConnection();

            return $this->container->make($readModel, ['connection' => $connection]);
        }

        return $this->container[$readModel];
    }
}
