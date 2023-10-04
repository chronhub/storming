<?php

declare(strict_types=1);

namespace Storm\Reporter;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Storm\Attribute\Loader;
use Storm\Contract\Reporter\MessageFilter;
use Storm\Contract\Reporter\Reporter;
use Storm\Contract\Reporter\ReporterManager;
use Storm\Reporter\Listener\FilterMessageListener;
use Storm\Reporter\Listener\NameReporterListener;
use Storm\Support\ContainerAsClosure;

use function array_keys;
use function array_merge;
use function array_unique;
use function array_values;

final class ReportingManager implements ReporterManager
{
    protected Container $container;

    protected array $aliases = [];

    protected array $reporters = [];

    public function __construct(
        protected Loader $map,
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
        $loaders = $this->list();

        foreach ($loaders as $class => $alias) {
            $this->container->singleton($class, function () use ($class) {
                return $this->create($class);
            });

            if ($alias !== $class) {
                $this->container->alias($class, $alias);
            }
        }
    }

    public function provides(): array
    {
        $list = $this->list();

        return array_unique(array_merge(array_keys($list), array_values($list)));
    }

    private function resolve(string $name): Reporter
    {
        [$class, $alias, $tracker, $filter] = $this->find($name);

        $instance = new $class($this->container[$tracker]);
        $instance->setContainer($this->container);

        if ($alias !== $class) {
            $this->aliases[$alias] = $class;
        }

        $this->addNameSubscriber($instance, $name);
        $this->addFilterSubscriber($instance, $filter);

        return $this->reporters[$class] = $instance;
    }

    private function resolved(string $name): ?Reporter
    {
        if (isset($this->reporters[$name])) {
            return $this->reporters[$name];
        }

        if (isset($this->aliases[$name])) {
            return $this->reporters[$this->aliases[$name]];
        }

        return null;
    }

    /**
     * @return array{class-string, non-empty-string, non-empty-string, non-empty-string}
     *
     * @throws InvalidArgumentException when reporter is not found
     */
    private function find(string $name): array
    {
        foreach ($this->map->getReporters() as $map) {
            if ($map['class'] === $name || $map['alias'] === $name) {
                return [$map['class'], $map['alias'], $map['tracker'], $map['filter']];
            }
        }

        throw new InvalidArgumentException("Reporter [$name] is not found.");
    }

    private function list(): array
    {
        return Arr::mapWithKeys($this->map->getReporters(), fn (array $map): array => [$map['class'] => $map['alias']]);
    }

    private function addFilterSubscriber(Reporter $reporter, string $filter): void
    {
        $messageFilter = $this->container[$filter];

        if (! $messageFilter instanceof MessageFilter) {
            throw new InvalidArgumentException('Filter must be instance of MessageFilter');
        }

        $subscriber = new FilterMessageListener($messageFilter);

        $reporter->subscribe($subscriber);
    }

    private function addNameSubscriber(Reporter $reporter, string $name): void
    {
        $reporter->subscribe(new NameReporterListener($name));
    }
}
