<?php

declare(strict_types=1);

namespace Storm\Attribute;

use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Storm\Reporter\Attribute\AsReporter;
use Storm\Reporter\Attribute\AsSubscriber;
use Storm\Support\Attribute\ReporterResolver;
use Storm\Support\Attribute\SubscriberResolver;
use Storm\Support\ContainerAsClosure;

use function is_object;

class AttributeFactory
{
    protected Container $container;

    protected array $resolvers = [
        AsReporter::class => ReporterResolver::class,
        AsSubscriber::class => SubscriberResolver::class,
    ];

    public function __construct(ContainerAsClosure $container)
    {
        $this->container = $container->container;
    }

    /**
     * @param class-string $attribute
     */
    public function make(string $attribute): SubscriberResolver|ReporterResolver
    {
        $resolver = $this->resolvers[$attribute] ?? null;

        if (is_object($resolver)) {
            return $resolver;
        }

        if ($resolver === null) {
            throw new InvalidArgumentException("Attribute $attribute is not defined");
        }

        // fixMe by now we do not support attribute inheritance

        return $this->resolvers[$attribute] = new $resolver($this->container);
    }
}
