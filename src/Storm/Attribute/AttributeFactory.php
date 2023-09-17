<?php

declare(strict_types=1);

namespace Storm\Attribute;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Collection;
use ReflectionClass;
use Storm\Attribute\Definition\MessageBuilder;
use Storm\Attribute\Definition\MessageHandlerResolver;
use Storm\Attribute\Definition\ReporterResolver;
use Storm\Attribute\Definition\SubscriberResolver;
use Storm\Reporter\Attribute\AsMessageHandler;
use Storm\Reporter\Attribute\AsReporter;
use Storm\Reporter\Attribute\AsSubscriber;

class AttributeFactory
{
    /**
     * Mapping of attribute types to resolver classes.
     *
     * @var array<class-string, class-string>
     */
    protected array $resolverMapping = [
        AsReporter::class => ReporterResolver::class,
        AsSubscriber::class => SubscriberResolver::class,
        AsMessageHandler::class => MessageHandlerResolver::class,
    ];

    /**
     * Ensure any reference bindings exists in the container when set
     */
    public function __construct(protected ?Container $container = null)
    {
    }

    /**
     * Find attribute definitions from the given classes.
     *
     * @param Collection<ReflectionClass> $classes
     * @return Collection{class-string, Collection<array<Definition|array<Definition>>>}
     */
    public function make(Collection $classes): Collection
    {
        $result = [];

        foreach ($this->resolverMapping as $attributeType => $resolverClass) {
            $definitions = $this->makeDefinition($classes, $resolverClass);

            $result[$attributeType] = $definitions;
        }

        //dd(collect($result));
        // build map
        $messageMap = new MessageBuilder();
        dd($messageMap->build($result[AsMessageHandler::class]));

        return collect($result[AsMessageHandler::class]);

        // dd($handlers);
    }

    /**
     * Make definition collections of a specific type from the given classes.
     *
     * @param Collection<ReflectionClass> $classes
     */
    protected function makeDefinition(Collection $classes, string $resolverClass): Collection
    {
        $resolver = new $resolverClass($this->container);

        return $resolver->find($classes);
    }
}
