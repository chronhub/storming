<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

use Storm\Contract\Serializer\SymfonySerializer;

interface SubscriptionFactory
{
    /**
     * Creates a query subscription.
     */
    public function createQuerySubscription(ProjectionOption $options): Subscriptor;

    /**
     * Creates a read model projector subscription.
     */
    public function createEmitterSubscription(string $streamName, ProjectionOption $options): Subscriptor;

    /**
     * Creates a read model subscription.
     */
    public function createReadModelSubscription(string $streamName, ReadModel $readModel, ProjectionOption $options): Subscriptor;

    /**
     * Get the projection provider.
     *
     * checkMe should be nullable
     */
    public function getProjectionProvider(): ProjectionProvider;

    /**
     * Get the serializer.
     */
    public function getSerializer(): SymfonySerializer;
}
