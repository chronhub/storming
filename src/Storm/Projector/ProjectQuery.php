<?php

declare(strict_types=1);

namespace Storm\Projector;

use Storm\Contract\Chronicler\QueryFilter;
use Storm\Contract\Projector\ContextReader;
use Storm\Contract\Projector\NotificationHub;
use Storm\Contract\Projector\QueryProjector;
use Storm\Contract\Projector\QuerySubscriber;
use Storm\Projector\Workflow\Notification\Command\CheckpointReset;
use Storm\Projector\Workflow\Notification\Command\UserStateRestored;

final readonly class ProjectQuery implements QueryProjector
{
    use InteractWithProjection;

    public function __construct(
        protected QuerySubscriber $subscriber,
        protected ContextReader $context,
    ) {}

    public function run(bool $inBackground): void
    {
        $this->describeIfNeeded();

        $this->subscriber->start($this->context, $inBackground);
    }

    public function reset(): void
    {
        $this->subscriber->interact(function (NotificationHub $hub): void {
            $hub->emit(CheckpointReset::class);

            $hub->emit(UserStateRestored::class);
        });
    }

    public function filter(QueryFilter $queryFilter): static
    {
        $this->context->withQueryFilter($queryFilter);

        return $this;
    }
}
