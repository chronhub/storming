<?php

declare(strict_types=1);

namespace Storm\Projector;

use Storm\Chronicler\InMemory\InMemoryEventStore;
use Storm\Chronicler\InMemory\InMemoryEventStreamProvider;
use Storm\Clock\ClockFactory;
use Storm\Contract\Projector\ContextReader;
use Storm\Contract\Projector\EmitterProjector;
use Storm\Contract\Projector\ProjectionOption;
use Storm\Contract\Projector\ProjectorManagerInterface;
use Storm\Contract\Projector\ProjectorMonitorInterface;
use Storm\Contract\Projector\QueryProjector;
use Storm\Contract\Projector\ReadModel;
use Storm\Contract\Projector\ReadModelProjector;
use Storm\Projector\Factory\EmitterSubscriptionFactory;
use Storm\Projector\Factory\QuerySubscriptionFactory;
use Storm\Projector\Factory\ReadModelSubscriptionFactory;
use Storm\Projector\Factory\SubscriptionBuilder;
use Storm\Projector\Options\ProjectionOptionResolver;
use Storm\Projector\Repository\InMemoryProvider;
use Storm\Projector\Workflow\DefaultContext;
use Storm\Serializer\JsonSerializerFactory;
use Storm\Tests\Feature\Projector\InMemory\Factory\ProjectionSerializerFactory;

final class ProjectorManager implements ProjectorManagerInterface
{
    private ?ProjectorMonitorInterface $monitor = null;

    private SubscriptionBuilder $builder;

    public function __construct(
        private readonly ProjectionOption|array $options,
    ) {
        $this->builder = $this->createInMemorySubscriptionBuilder();
    }

    public function newQueryProjector(array $options = []): QueryProjector
    {
        $options = $this->createOption($options);

        $subscription = (new QuerySubscriptionFactory($this->builder))->create($options);

        $context = $this->createContextBuilder();

        return new ProjectQuery($subscription, $context);
    }

    public function newEmitterProjector(string $streamName, array $options = []): EmitterProjector
    {
        $options = $this->createOption($options);

        $subscription = (new EmitterSubscriptionFactory($this->builder))->create($streamName, $options);

        $context = $this->createContextBuilder();

        return new ProjectEmitter($subscription, $context, $streamName);
    }

    public function newReadModelProjector(string $streamName, ReadModel $readModel, array $options = []): ReadModelProjector
    {
        $options = $this->createOption($options);

        $subscription = (new ReadModelSubscriptionFactory($this->builder))->create($streamName, $readModel, $options);

        $context = $this->createContextBuilder();

        return new ProjectReadModel($subscription, $context, $streamName);
    }

    public function monitor(): ProjectorMonitorInterface
    {
        return $this->monitor ??= new ProjectorMonitor(
            $this->builder->projectionProvider,
            $this->builder->serializer,
        );
    }

    public function getSubscriptionBuilder(): SubscriptionBuilder
    {
        return $this->builder;
    }

    /**
     * Creates a projection option instance with the specified options.
     *
     * @param array<ProjectionOption::*, null|string|int|bool|array> $options
     *
     * @see DefaultOption
     */
    private function createOption(array $options = []): ProjectionOption
    {
        $resolver = new ProjectionOptionResolver($this->options);

        return $resolver($options);
    }

    // todo service manager
    private function createInMemorySubscriptionBuilder(): SubscriptionBuilder
    {
        $eventStreamProvider = new InMemoryEventStreamProvider();
        $clock = ClockFactory::create();
        $serializerFactory = new ProjectionSerializerFactory(
            new JsonSerializerFactory()
        );

        return new SubscriptionBuilder(
            new InMemoryEventStore($eventStreamProvider),
            new InMemoryProvider($clock),
            $eventStreamProvider,
            $clock,
            $serializerFactory->make(),
        );
    }

    private function createContextBuilder(): ContextReader
    {
        return new DefaultContext();
    }
}
