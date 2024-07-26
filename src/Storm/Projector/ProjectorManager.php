<?php

declare(strict_types=1);

namespace Storm\Projector;

use Storm\Contract\Projector\ContextReader;
use Storm\Contract\Projector\EmitterProjector;
use Storm\Contract\Projector\ProjectionOption;
use Storm\Contract\Projector\ProjectionQueryScope;
use Storm\Contract\Projector\ProjectorManagerInterface;
use Storm\Contract\Projector\ProjectorMonitorInterface;
use Storm\Contract\Projector\QueryProjector;
use Storm\Contract\Projector\ReadModel;
use Storm\Contract\Projector\ReadModelProjector;
use Storm\Contract\Projector\SubscriptionFactory;
use Storm\Projector\Options\ProjectionOptionResolver;
use Storm\Projector\Workflow\DefaultContext;

final class ProjectorManager implements ProjectorManagerInterface
{
    private ?ProjectorMonitorInterface $monitor = null;

    public function __construct(
        private readonly SubscriptionFactory $factory,
        private readonly ProjectionOption|array $options,
        private readonly ?ProjectionQueryScope $queryScope = null,
    ) {}

    public function newQueryProjector(array $options = []): QueryProjector
    {
        $options = $this->createOption($options);
        $subscription = $this->factory->createQuerySubscription($options);
        $context = $this->createContextBuilder();

        return new ProjectQuery($subscription, $context);
    }

    public function newEmitterProjector(string $streamName, array $options = []): EmitterProjector
    {
        $options = $this->createOption($options);
        $subscription = $this->factory->createEmitterSubscription($streamName, $options);
        $context = $this->createContextBuilder();

        return new ProjectEmitter($subscription, $context, $streamName);
    }

    public function newReadModelProjector(string $streamName, ReadModel $readModel, array $options = []): ReadModelProjector
    {
        $options = $this->createOption($options);
        $subscription = $this->factory->createReadModelSubscription($streamName, $readModel, $options);
        $context = $this->createContextBuilder();

        return new ProjectReadModel($subscription, $context, $streamName);
    }

    public function queryScope(): ?ProjectionQueryScope
    {
        return $this->queryScope;
    }

    public function monitor(): ProjectorMonitorInterface
    {
        return $this->monitor ??= new ProjectorMonitor(
            $this->factory->getProjectionProvider(),
            $this->factory->getSerializer(),
        );
    }

    /**
     * Creates a ProjectionOption instance with the specified options.
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

    private function createContextBuilder(): ContextReader
    {
        return new DefaultContext();
    }
}
