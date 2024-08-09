<?php

declare(strict_types=1);

namespace Storm\Projector\Factory;

use Storm\Projector\Connector\ConnectionManager;
use Storm\Projector\Exception\InvalidArgumentException;

class ProviderResolverFactory
{
    /** @var array<string, string|class-string<ProviderFactory>|ProviderFactory> */
    protected array $factories = [
        'query' => QueryProviderFactory::class,
        'emitter' => EmitterProviderFactory::class,
        'read_model' => ReadModelProviderFactory::class,
    ];

    public function resolve(string $name, ConnectionManager $manager): ProviderFactory
    {
        $factory = $this->factories[$name] ?? null;

        if (! $factory) {
            throw new InvalidArgumentException("Subscription resolver factory $name not found");
        }

        if ($factory instanceof ProviderFactory) {
            return $factory;
        }

        if (app()->bound($factory)) {
            return app($factory);
        }

        return new $this->factories[$name]($manager);
    }

    // checkMe wip contracts tests
    public function addFactory(string $name, ProviderFactory|string $factory): void
    {
        $this->factories[$name] = $factory;
    }
}
