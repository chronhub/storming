<?php

declare(strict_types=1);

namespace Storm\Reporter;

use Closure;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Storm\Attribute\Loader;
use Storm\Contract\Reporter\SubscriberManager;
use Storm\Contract\Tracker\Listener;
use Storm\Reporter\Concerns\InteractWithManager;
use Storm\Support\ContainerAsClosure;
use Storm\Tracker\ResolvedListener;

use function array_map;
use function class_exists;
use function is_callable;

final class SubscribingManager implements SubscriberManager
{
    use InteractWithManager;

    private array $subscribers = [];

    private array $provides = [];

    private array $loaded;

    protected Container $container;

    public function __construct(Loader $loader, ContainerAsClosure $container)
    {
        $this->loaded = $loader->getSubscribers();
        $this->container = $container->container;
    }

    public function register(string $name): void
    {
        if (! class_exists($name)) {
            throw new InvalidArgumentException("Subscriber $name must be a class");
        }

        if (isset($this->subscribers[$name])) {
            throw new InvalidArgumentException("Subscriber $name is already registered");
        }

        $this->subscribers[$name] = $this->resolve($name, $this->loaded[$name]['events']);
    }

    public function wire(): void
    {
        foreach ($this->loaded as $class => $events) {
            $this->subscribers[$class] = $this->resolve($class, $events);
        }
    }

    public function provides(): array
    {
        return $this->provides;
    }

    private function resolve(string $name, array $events): Closure
    {
        $listener = $this->createInstance($name, $events['references']);

        $instance = fn (): array => $this->toCallable($listener, $events);

        $this->container->bind($name, $instance);

        $this->provides[] = $name;

        return $instance;
    }

    private function toCallable(object $instance, array $handlers): array
    {
        return array_map(function (array $handler) use ($instance): Listener {
            if (! is_callable($instance)) {
                $instance = Closure::fromCallable([$instance, $handler['method']]);
            }

            return new ResolvedListener($instance, $handler['event'], $handler['priority']);
        }, $handlers['events']);
    }
}
