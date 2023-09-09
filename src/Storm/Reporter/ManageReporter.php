<?php

declare(strict_types=1);

namespace Storm\Reporter;

use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use Storm\Contract\Reporter\Reporter;
use Storm\Reporter\Attribute\AsReporter;
use Storm\Reporter\Subscriber\FilterMessage;
use Storm\Reporter\Subscriber\NameReporter;
use Storm\Support\Attribute\AttributeResolver;
use Storm\Support\ContainerAsClosure;

use function class_exists;
use function is_string;

class ManageReporter
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
        protected AttributeResolver $attributeResolver
    ) {
        $this->container = $container->container;
    }

    public function create(string $name): Reporter
    {
        $className = $this->aliases[$name] ?? $name;

        if (! class_exists($className)) {
            // todo fetch attribute to determine reporter alias if exists
            throw new InvalidArgumentException("Reporter $className does not exist.");
        }

        if (isset($this->reporters[$className])) {
            return $this->reporters[$className];
        }

        [$alias, $reporter] = $this->resolveReporter($className);

        $this->aliases[$alias] = $reporter::class;

        return $this->reporters[$className] = $reporter;
    }

    public function addAlias(string $name, string $className): void
    {
        if (isset($this->aliases[$name])) {
            throw new InvalidArgumentException("Reporter alias $name already exists.");
        }

        if (! class_exists($className)) {
            throw new InvalidArgumentException("Reporter $className does not exist.");
        }

        $this->aliases[$name] = $className;
    }

    /**
     * @return array{class-string, Reporter}
     *
     * @throws ReflectionException
     */
    protected function resolveReporter(string $className): array
    {
        $reflectionClass = new ReflectionClass($className);

        return $this->attributeResolver->forClass($reflectionClass)
            ->filter(function ($attribute) {
                return $attribute instanceof AsReporter;
            })->whenEmpty(function () use ($className) {
                throw new InvalidArgumentException("Missing #AsReporter attribute for class $className");
            })
            ->map(function (AsReporter $attribute) use ($reflectionClass) {
                $instance = $this->attributeResolver->newInstance($reflectionClass);

                $filter = $attribute->filter;

                if (is_string($filter)) {
                    $filter = $this->attributeResolver->container[$filter];
                }

                $alias = $attribute->name ?? $reflectionClass->getName();

                $instance->subscribe(
                    new NameReporter($alias),
                    new FilterMessage($filter)
                );

                return [$alias, $instance];

            })->first();
    }
}
