<?php

declare(strict_types=1);

namespace Storm\Projector\Connector;

use Storm\Projector\Exception\InvalidArgumentException;
use Storm\Projector\Factory\EmitterProviderFactory;
use Storm\Projector\Factory\ProviderFactory;
use Storm\Projector\Factory\QueryProviderFactory;
use Storm\Projector\Factory\ReadModelProviderFactory;

class SubscriptionFactoryResolver
{
    /** @var array<string, class-string<ProviderFactory>|ProviderFactory> */
    protected array $factories = [
        'query' => QueryProviderFactory::class,
        'emitter' => EmitterProviderFactory::class,
        'read_model' => ReadModelProviderFactory::class,
    ];

    public function resolve(string $name, ConnectionManager $manager): ProviderFactory
    {
        $factory = $this->factories[$name] ?? null;

        if (! $factory) {
            throw new InvalidArgumentException("Invalid provider factory type: $name");
        }

        if ($factory instanceof ProviderFactory) {
            return $factory;
        }

        if (app()->bound($factory)) {
            return app($factory);
        }

        return new $this->factories[$name]($manager);
    }

    public function addFactory(string $name, ProviderFactory|string $factory): void
    {
        $this->factories[$name] = $factory;
    }
}
