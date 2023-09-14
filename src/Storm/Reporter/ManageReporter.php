<?php

declare(strict_types=1);

namespace Storm\Reporter;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use ReflectionException;
use Storm\Attribute\Loader;
use Storm\Attribute\ResolverFactory;
use Storm\Contract\Reporter\MessageFilter;
use Storm\Contract\Reporter\Reporter;
use Storm\Contract\Reporter\ReporterManager;
use Storm\Contract\Tracker\Listener;
use Storm\Reporter\Attribute\AsReporter;
use Storm\Reporter\Attribute\AsSubscriber;
use Storm\Reporter\Subscriber\FilterMessage;
use Storm\Reporter\Subscriber\NameReporter;
use Storm\Support\Attribute\ReporterInstance;
use Storm\Support\Attribute\ReporterResolver;
use Storm\Support\Attribute\SubscriberResolver;
use Storm\Support\ContainerAsClosure;
use Storm\Tracker\GenericListener;
use Storm\Tracker\ResolvedListener;

use function is_object;
use function is_string;

final class ManageReporter implements ReporterManager
{
    protected Container $container;

    /**
     * @var array <string, Reporter>
     */
    protected array $reporters = [];

    public function __construct(
        ContainerAsClosure $container,
        protected ResolverFactory $attributeFactory,
        protected Loader $loader,
    ) {
        $this->container = $container->container;
    }

    public function create(string $name): Reporter
    {
        $aliases = $this->loader->getReporter($name);

        if ($aliases === null) {
            throw new InvalidArgumentException("Reporter $name does not exist");
        }

        [$className, $alias] = $aliases;

        return $this->reporters[$alias] ??= $this->resolve($className);
    }

    protected function resolve(string $className): Reporter
    {
        /** @var ReporterResolver $resolver */
        $resolver = $this->attributeFactory->make(AsReporter::class);

        /** @var ReporterInstance $instance */
        $instance = $resolver->resolve($className);

        $this->setSubscriberResolver($instance->reporter);

        $this->addSubscriber(
            $instance->reporter,
            $this->createReporterNameSubscriber($instance->name),
            $this->createMessageFilterSubscriber($instance->messageFilter),
        );

        return $instance->reporter;
    }

    protected function setSubscriberResolver(Reporter $reporter): void
    {
        $reporter->withSubscriberResolver(
            function (string|object $subscriber) {
                return $this->attributeFactory->make(AsSubscriber::class)->resolve($subscriber);
            }
        );
    }

    protected function addSubscriber(Reporter $reporter, object ...$subscribers): void
    {
        foreach ($subscribers as $subscriber) {
            $listeners = $this->resolveSubscriber($subscriber);

            $reporter->subscribe(...$listeners->all());
        }
    }

    /**
     * @return Collection<Listener|GenericListener|ResolvedListener>
     *
     * @throws ReflectionException
     */
    protected function resolveSubscriber(string|object $subscriber): Collection
    {
        /** @var SubscriberResolver $resolver */
        $resolver = $this->attributeFactory->make(AsSubscriber::class);

        $listeners = $resolver->resolve($subscriber);

        return $listeners->each(function (object $listener) use ($subscriber) {
            if (is_object($subscriber)) {
                return new ResolvedListener($subscriber, $listener->name(), $listener->priority());
            }

            return $listener;
        });
    }

    protected function createReporterNameSubscriber(string $name): NameReporter
    {
        return new NameReporter($name);
    }

    protected function createMessageFilterSubscriber(string|MessageFilter $messageFilter): ?FilterMessage
    {
        if (is_string($messageFilter)) {
            $messageFilter = $this->container[$messageFilter];
        }

        return new FilterMessage($messageFilter);
    }
}
