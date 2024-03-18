<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

use Storm\Serializer\JsonSerializer;

interface SubscriptionFactory
{
    public function createQuerySubscription(ProjectionOption $option): QuerySubscriber;

    public function createEmitterSubscription(string $streamName, ProjectionOption $option): EmitterSubscriber;

    public function createReadModelSubscription(string $streamName, ReadModel $readModel, ProjectionOption $option): ReadModelSubscriber;

    /**
     * Creates a ProjectionOption instance with the specified options.
     *
     * @param array<ProjectionOption::*, null|string|int|bool|array> $options
     *
     * @see DefaultOption
     */
    public function createOption(array $options = []): ProjectionOption;

    /**
     * Creates a ContextReader.
     */
    public function createContextBuilder(): ContextReader;

    /**
     * Get the projection provider.
     */
    public function getProjectionProvider(): ProjectionProvider;

    /**
     * Get the projection serializer.
     */
    public function getSerializer(): JsonSerializer;

    /**
     * Get the projection query scope.
     */
    public function getQueryScope(): ?ProjectionQueryScope;
}
