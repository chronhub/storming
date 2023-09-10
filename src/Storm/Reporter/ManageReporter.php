<?php

declare(strict_types=1);

namespace Storm\Reporter;

use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Storm\Attribute\AttributeFactory;
use Storm\Contract\Reporter\MessageFilter;
use Storm\Contract\Reporter\Reporter;
use Storm\Contract\Reporter\ReporterManager;
use Storm\Reporter\Attribute\AsReporter;
use Storm\Reporter\Attribute\AsSubscriber;
use Storm\Reporter\Subscriber\FilterMessage;
use Storm\Reporter\Subscriber\NameReporter;
use Storm\Support\Attribute\ReporterResolver;
use Storm\Support\ContainerAsClosure;

use function class_exists;

final class ManageReporter implements ReporterManager
{
    protected Container $container;

    /**
     * @var array <string, Reporter>
     */
    protected array $reporters = [];

    /**
     * @var array <string, class-string>
     */
    protected array $aliases = [
        'command-default' => ReportCommand::class,
    ];

    public function __construct(
        ContainerAsClosure $container,
        protected AttributeFactory $attributeFactory
    ) {
        $this->container = $container->container;
    }

    public function create(string $name): Reporter
    {
        // only use alias till we fetch attributes from autoload classes
        // could be done on resolving by adding aliases with autoload classes
        $className = $this->aliases[$name] ?? $name;

        if (! class_exists($className)) {
            // todo fetch attribute to determine reporter alias if exists
            throw new InvalidArgumentException("Reporter $className does not exist");
        }

        return $this->reporters[$className] ??= $this->resolve($className);
    }

    public function addAlias(string $name, string $className): void
    {
        if (isset($this->aliases[$name])) {
            throw new InvalidArgumentException("Reporter alias $name already exists");
        }

        if (! class_exists($className)) {
            throw new InvalidArgumentException("Reporter $className does not exist");
        }

        $this->aliases[$name] = $className;
    }

    protected function resolve(string $className): Reporter
    {
        $resolver = $this->attributeFactory->make(AsReporter::class);

        if (! $resolver instanceof ReporterResolver) {
            throw new InvalidArgumentException("Resolver for $className is not a ReporterResolver");
        }

        [$instance, $alias, $filter] = $resolver->resolve($className);

        $this->setSubscriberResolver($instance);
        $this->addReporterAliasSubscriber($instance, $alias);
        $this->addMessageFilterSubscriber($instance, $filter);

        return $instance;
    }

    protected function setSubscriberResolver(Reporter $reporter): void
    {
        $reporter->withSubscriberResolver(
            function (string|object $subscriber) {
                return $this->attributeFactory->make(AsSubscriber::class)->resolve($subscriber);
            }
        );
    }

    protected function addMessageFilterSubscriber(Reporter $reporter, MessageFilter $messageFilter): void
    {
        $reporter->subscribe(new FilterMessage($messageFilter));
    }

    protected function addReporterAliasSubscriber(Reporter $reporter, string $alias): void
    {
        $reporter->subscribe(new NameReporter($alias));
    }
}
