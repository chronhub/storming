<?php

declare(strict_types=1);

namespace Storm\Aggregate;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use InvalidArgumentException;
use Storm\Contract\Aggregate\AggregateFactory;
use Storm\Contract\Aggregate\AggregateManager;
use Storm\Contract\Aggregate\AggregateRepository;

use function is_array;

final class DefaultAggregateManager implements AggregateManager
{
    /** @var array<string, (Closure(Application): AggregateFactory)>|array */
    private array $factories = [];

    /** @var array<string, AggregateRepository>|array */
    private array $repositories = [];

    public function __construct(
        private readonly Application $app
    ) {}

    public function create(string $name, ?string $factory = null): AggregateRepository
    {
        return $this->repositories[$name] ??= $this->resolve($name, $factory ?? 'default');
    }

    public function addFactory(string $name, Closure $factory): void
    {
        $this->factories[$name] = $factory;
    }

    private function resolve(string $name, string $factory): AggregateRepository
    {
        $repository = $this->make($factory);

        $config = $this->getConfig($name);

        return $repository->make($config)->create();
    }

    /**
     * @throws InvalidArgumentException When no factory is found for the given name.
     */
    private function make(string $name): AggregateFactory
    {
        if (! isset($this->factories[$name])) {
            throw new InvalidArgumentException("No factory found for aggregate key [$name]");
        }

        return $this->factories[$name]($this->app);
    }

    /**
     * @throws InvalidArgumentException When no configuration is found for the given name.
     */
    private function getConfig(string $name): array
    {
        $config = $this->app['config']->get("aggregates.repositories.$name");

        if (! is_array($config) || $config === []) {
            throw new InvalidArgumentException("No configuration found for aggregate key [$name]");
        }

        return $config;
    }
}
