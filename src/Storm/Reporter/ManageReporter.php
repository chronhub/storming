<?php

declare(strict_types=1);

namespace Storm\Reporter;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Storm\Attribute\Loader;
use Storm\Attribute\ResolverFactory;
use Storm\Contract\Reporter\MessageFilter;
use Storm\Contract\Reporter\Reporter;
use Storm\Contract\Reporter\ReporterManager;
use Storm\Contract\Tracker\Listener;
use Storm\Reporter\Subscriber\FilterMessage;
use Storm\Reporter\Subscriber\NameReporter;
use Storm\Support\ContainerAsClosure;
use Storm\Tracker\GenericListener;
use Storm\Tracker\ResolvedListener;

use function is_object;
use function is_string;

final class ManageReporter implements ReporterManager
{
    protected Container $container;

    /**
     * @var array<class-string|non-empty-string, Reporter>
     */
    protected array $reporters = [];

    public function __construct(
        ContainerAsClosure $container,
        protected ResolverFactory $resolver,
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

    private function resolve(string $className): Reporter
    {
        $instance = $this->resolver->toReporter($className);

        $reporter = $instance->reporter;

        $this->setSubscriberResolver($reporter);

        $this->addSubscriber(
            $reporter,
            $this->createReporterNameSubscriber($instance->name),
            $this->createMessageFilterSubscriber($instance->messageFilter),
        );

        return $reporter;
    }

    private function setSubscriberResolver(Reporter $reporter): void
    {
        $reporter->withSubscriberResolver(
            function (string|object $subscriber) {
                return $this->resolver->toSubscriber($subscriber);
            }
        );
    }

    private function addSubscriber(Reporter $reporter, object ...$subscribers): void
    {
        foreach ($subscribers as $subscriber) {
            $listeners = $this->resolveSubscriber($subscriber);

            $reporter->subscribe(...$listeners->all());
        }
    }

    /**
     * @return Collection<Listener|GenericListener|ResolvedListener>
     */
    private function resolveSubscriber(string|object $subscriber): Collection
    {
        $listeners = $this->resolver->toSubscriber($subscriber);

        return $listeners->each(function (object $listener) use ($subscriber) {
            if (is_object($subscriber)) {
                return new ResolvedListener($subscriber, $listener->name(), $listener->priority());
            }

            return $listener;
        });
    }

    private function createReporterNameSubscriber(string $name): NameReporter
    {
        return new NameReporter($name);
    }

    private function createMessageFilterSubscriber(string|MessageFilter $messageFilter): FilterMessage
    {
        if (is_string($messageFilter)) {
            $messageFilter = $this->container[$messageFilter];
        }

        return new FilterMessage($messageFilter);
    }
}
