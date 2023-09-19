<?php

declare(strict_types=1);

namespace Storm\Attribute;

use Closure;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Foundation\Application;
use Storm\Contract\Tracker\Listener;
use Storm\Support\ContainerAsClosure;
use Storm\Support\MessageAliasBinding;
use Storm\Tracker\ResolvedListener;

use function array_map;
use function is_callable;

class ServiceRegistry
{
    protected Container $container;

    public function __construct(ContainerAsClosure $container, protected Loader $loader)
    {
        $this->container = $container->container;
    }

    public function register(): void
    {
        $this->registerReporters($this->loader->getReporters());
        $this->registerSubscribers($this->loader->getSubscribers());
        $this->registerHandlers($this->loader->getHandlers());
    }

    protected function registerReporters(array $reporters): void
    {
        foreach ($reporters as $reporter) {
            $this->container->singleton($reporter['alias'], function (Application $app) use ($reporter) {
                return $app->make($reporter['class'], [$app[$reporter['tracker']]]);
            });

            if ($reporter['alias'] !== $reporter['class']) {
                $this->container->alias($reporter['class'], $reporter['alias']);
            }
        }
    }

    protected function registerHandlers(array $messageHandlers): void
    {
        foreach ($messageHandlers as $messageName => $handlers) {
            $aliasMessage = MessageAliasBinding::fromMessageName($messageName);

            $this->container->bind($aliasMessage, fn (Application $app): array => $this->handlerToCallable($handlers));
        }
    }

    protected function handlerToCallable(array $handlers): array
    {
        $instances = [];

        return array_map(function (array $handler) use (&$instances) {
            if ($handler['method'] === '__invoke') {
                return $this->createInstance($handler['class'], $handler['references']);
            }

            $class = $handler['class'];

            if (! isset($instances[$class])) {
                $instances[$class] = $this->createInstance($class, $handler['references']);
            }

            return Closure::fromCallable([$instances[$class], $handler['method']]);
        }, $handlers);
    }

    protected function registerSubscribers(array $subscribers): void
    {
        // don't bind every subscriber, but those in a config
        // which will be attached to a reporter

        $instances = [];

        foreach ($subscribers as $class => $events) {
            if (! isset($instances[$class])) {
                $instances[$class] = $this->createInstance($class, $events['references']);
            }

            $subscriber = fn (): array => $this->subscriberToCallable($instances[$class], $events);

            $this->container->bind($class, $subscriber);
        }
    }

    protected function subscriberToCallable(object $instance, array $handlers): array
    {
        return array_map(function (array $handler) use ($instance): Listener {
            if (! is_callable($instance)) {
                $instance = Closure::fromCallable([$instance, $handler['method']]);
            }

            return new ResolvedListener($instance, $handler['event'], $handler['priority']);
        }, $handlers['events']);
    }

    protected function createInstance(string $class, array $references): object
    {
        $parameters = $references['__construct'] ?? [];

        if ($parameters !== []) {
            return $this->container->make($class, ...$parameters);
        }

        return $this->container[$class];
    }

    protected function callMethods(object $class, array $references): array
    {
        $instances = [];

        foreach ($references as $method => $parameters) {
            if ($method === '__construct') {
                continue;
            }

            $parameters = $references[$method] ?? [];

            if ($parameters === []) {
                continue;
            }

            $instances[$method] = $this->container->call(fn () => $class, $parameters);
        }

        return $instances;
    }
}
