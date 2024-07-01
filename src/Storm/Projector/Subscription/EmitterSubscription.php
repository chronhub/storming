<?php

declare(strict_types=1);

namespace Storm\Projector\Subscription;

use Storm\Contract\Projector\ActivityFactory;
use Storm\Contract\Projector\EmitterManagement;
use Storm\Contract\Projector\EmitterScope;
use Storm\Contract\Projector\EmitterSubscriber;
use Storm\Contract\Projector\Subscriptor;

final readonly class EmitterSubscription implements EmitterSubscriber
{
    use InteractWithPersistentSubscription;

    public function __construct(
        protected Subscriptor $subscriptor,
        protected EmitterManagement $management,
        protected ActivityFactory $activities,
        protected EmitterScope $scope
    ) {}
}
