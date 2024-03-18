<?php

declare(strict_types=1);

namespace Storm\Projector\Subscription;

use Storm\Contract\Projector\ActivityFactory;
use Storm\Contract\Projector\ReadModelManagement;
use Storm\Contract\Projector\ReadModelScope;
use Storm\Contract\Projector\ReadModelSubscriber;
use Storm\Contract\Projector\Subscriptor;

final readonly class ReadModelSubscription implements ReadModelSubscriber
{
    use InteractWithPersistentSubscription;

    public function __construct(
        protected Subscriptor $subscriptor,
        protected ReadModelManagement $management,
        protected ActivityFactory $activities,
        protected ReadModelScope $scope
    ) {
    }
}
