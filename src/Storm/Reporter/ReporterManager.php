<?php

declare(strict_types=1);

namespace Storm\Reporter;

use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Storm\Contract\Reporter\MessageFilter;
use Storm\Contract\Reporter\Reporter;
use Storm\Reporter\Listener\FilterMessageListener;
use Storm\Reporter\Listener\NameReporterListener;
use Storm\Support\ContainerAsClosure;

use function array_keys;
use function array_merge;
use function array_unique;
use function array_values;

class ReporterManager
{
    protected Container $container;

    protected array $aliases = [];

    protected array $reporters = [];

    public function __construct(
        protected ReporterMap $map,
        ContainerAsClosure $container
    ) {
        $this->container = $container->container;
    }

    public function create(string $name): Reporter
    {
        if ($instance = $this->resolved($name)) {
            return $instance;
        }

        return $this->resolve($name);
    }

    public function wire(): void
    {
        $loaders = $this->map->list();

        foreach ($loaders as $class => $alias) {
            $this->container->singleton($class, function () use ($class) {
                return $this->create($class);
            });

            if ($alias !== $class) {
                $this->container->alias($class, $alias);
            }
        }
    }

    public function getLoaded(): array
    {
        $list = $this->map->list();

        return array_unique(array_merge(array_keys($list), array_values($list)));
    }

    protected function resolve(string $name): Reporter
    {
        [$class, $alias, $tracker, $filter] = $this->load($name);

        $instance = new $class($this->container[$tracker]);
        $instance->setContainer($this->container);

        if ($alias !== $class) {
            $this->aliases[$alias] = $class;
        }

        $this->addNameSubscriber($instance, $name);
        $this->addFilterSubscriber($instance, $filter);

        return $this->reporters[$class] = $instance;
    }

    protected function load(string $name): array
    {
        $reporter = $this->map->find($name);

        if ($reporter === null) {
            throw new InvalidArgumentException("Reporter [$name] is not found.");
        }

        return $reporter;
    }

    protected function resolved(string $name): ?Reporter
    {
        if (isset($this->reporters[$name])) {
            return $this->reporters[$name];
        }

        if (isset($this->aliases[$name])) {
            return $this->reporters[$this->aliases[$name]];
        }

        return null;
    }

    protected function addFilterSubscriber(Reporter $reporter, string $filter): void
    {
        $messageFilter = $this->container[$filter];

        if (! $messageFilter instanceof MessageFilter) {
            throw new InvalidArgumentException('Filter must be instance of MessageFilter');
        }

        $subscriber = new FilterMessageListener($messageFilter);

        $reporter->subscribe($subscriber);
    }

    protected function addNameSubscriber(Reporter $reporter, string $name): void
    {
        $reporter->subscribe(new NameReporterListener($name));
    }
}
