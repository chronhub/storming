<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\SerializerInterface;

interface SubscriptionFactory
{
    /**
     * Creates a query subscription.
     */
    public function createQuerySubscription(ProjectionOption $option): QuerySubscriber;

    /**
     * Creates a read model projector subscription.
     */
    public function createEmitterSubscription(string $streamName, ProjectionOption $option): EmitterSubscriber;

    /**
     * Creates a read model subscription.
     */
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
     * Creates the context reader.
     */
    public function createContextBuilder(): ContextReader;

    /**
     * Get the projection provider.
     */
    public function getProjectionProvider(): ProjectionProvider;

    /**
     * Get the projection serializer.
     */
    public function getSerializer(): SerializerInterface&EncoderInterface&DecoderInterface;

    /**
     * Get the projection query scope.
     */
    public function getQueryScope(): ?ProjectionQueryScope;
}
