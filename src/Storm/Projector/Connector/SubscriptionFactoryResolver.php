<?php

declare(strict_types=1);

namespace Storm\Projector\Connector;

use Storm\Projector\Exception\InvalidArgumentException;
use Storm\Projector\Factory\EmitterSubscriptionFactory;
use Storm\Projector\Factory\QuerySubscriptionFactory;
use Storm\Projector\Factory\ReadModelSubscriptionFactory;
use Storm\Projector\Factory\SubscriptionFactory;

class SubscriptionFactoryResolver
{
    /** @var array<string, class-string<SubscriptionFactory>|SubscriptionFactory> */
    protected array $factories = [
        'query' => QuerySubscriptionFactory::class,
        'emitter' => EmitterSubscriptionFactory::class,
        'read_model' => ReadModelSubscriptionFactory::class,
    ];

    public function resolve(string $name, ConnectionManager $manager): SubscriptionFactory
    {
        $factory = $this->factories[$name] ?? null;

        if (! $factory) {
            throw new InvalidArgumentException("Invalid subscription type: $name");
        }

        if ($factory instanceof SubscriptionFactory) {
            return $factory;
        }

        if (app()->bound($factory)) {
            return app($factory);
        }

        return new $this->factories[$name]($manager);
    }

    public function addFactory(string $name, SubscriptionFactory|string $factory): void
    {
        $this->factories[$name] = $factory;
    }
}
