<?php

declare(strict_types=1);

namespace Storm\Projector;

use Storm\Contract\Projector\ContextReader;
use Storm\Contract\Projector\EmitterProjector;
use Storm\Contract\Projector\EmitterSubscriber;
use Storm\Contract\Projector\NotificationHub;
use Storm\Contract\Projector\ProjectionQueryFilter;
use Storm\Projector\Support\Notification\Management\ProjectionDiscarded;
use Storm\Projector\Support\Notification\Management\ProjectionRevised;

final readonly class ProjectEmitter implements EmitterProjector
{
    use InteractWithProjection;

    public function __construct(
        protected EmitterSubscriber $subscriber,
        protected ContextReader $context,
        protected string $streamName
    ) {
    }

    public function run(bool $inBackground): void
    {
        $this->describeIfNeeded();

        $this->subscriber->start($this->context, $inBackground);
    }

    public function reset(): void
    {
        $this->subscriber->interact(
            fn (NotificationHub $hub) => $hub->trigger(new ProjectionRevised())
        );
    }

    public function filter(ProjectionQueryFilter $queryFilter): static
    {
        $this->context->withQueryFilter($queryFilter);

        return $this;
    }

    public function delete(bool $deleteEmittedEvents): void
    {
        $this->subscriber->interact(
            fn (NotificationHub $hub) => $hub->trigger(new ProjectionDiscarded($deleteEmittedEvents))
        );
    }

    public function getName(): string
    {
        return $this->streamName;
    }
}
