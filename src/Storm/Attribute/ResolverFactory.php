<?php

declare(strict_types=1);

namespace Storm\Attribute;

use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Storm\Reporter\Attribute\AsMessageHandler;
use Storm\Reporter\Attribute\AsReporter;
use Storm\Reporter\Attribute\AsSubscriber;
use Storm\Support\Attribute\MessageHandlerResolver;
use Storm\Support\Attribute\ReporterResolver;
use Storm\Support\Attribute\SubscriberResolver;

use function array_key_exists;
use function is_object;

class ResolverFactory
{
    /**
     * @var array<class-string, class-string|object>
     */
    protected array $resolvers = [
        AsReporter::class => ReporterResolver::class,
        AsSubscriber::class => SubscriberResolver::class,
        AsMessageHandler::class => MessageHandlerResolver::class,
    ];

    public function __construct(protected Container $container)
    {
    }

    /**
     * @param class-string $attribute
     */
    public function make(string $attribute): object
    {
        $resolver = $this->resolvers[$attribute] ?? null;

        if (is_object($resolver)) {
            return $resolver;
        }

        if ($resolver === null) {
            throw new InvalidArgumentException("Attribute $attribute is not defined");
        }

        return $this->resolvers[$attribute] = new $resolver($this->container);
    }

    /**
     * @param class-string $resolver
     */
    public function has(string $resolver): bool
    {
        return array_key_exists($resolver, $this->resolvers);
    }
}
