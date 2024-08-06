<?php

declare(strict_types=1);

namespace Storm\Projector\Factory;

use Storm\Contract\Projector\ReadModel;
use Storm\Projector\Options\Option;
use Storm\Projector\Provider\Subscriptor;

interface ProviderFactory
{
    /**
     * Create a new subscription.
     */
    public function create(?string $streamName, ?ReadModel $readModel, Option $options): Subscriptor;
}
