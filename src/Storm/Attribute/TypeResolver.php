<?php

declare(strict_types=1);

namespace Storm\Attribute;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionException;
use Storm\Chronicler\Exceptions\RuntimeException;

use function sprintf;

abstract class TypeResolver
{
    public const NO_ATTRIBUTE_FOUND = 'No attribute %s found in class %s';

    public function __construct(protected readonly Container $container)
    {
    }

    public function resolve(string|object $class): mixed
    {
        $reflectionClass = ReflectionUtil::createReflectionClass($class);

        $attributes = ReflectionUtil::getAttributesInstancesForClass($reflectionClass, $this->getSupportedAttribute());

        return $this->process($attributes, $reflectionClass, $class);
    }

    /**
     * Create a new instance of a class considering constructor dependencies.
     *
     * @throws ReflectionException
     */
    protected function createInstance(object $reflectionClass, array $parameters = []): object
    {
        if (! $reflectionClass instanceof ReflectionClass) {
            return $reflectionClass;
        }

        if ($parameters !== []) {
            return $reflectionClass->newInstance(...$parameters);
        }

        $constructorParameters = ReflectionUtil::getConstructorParameters($reflectionClass);

        if ($constructorParameters === []) {
            return $reflectionClass->newInstance();
        }

        $bindings = ReflectionUtil::getReferenceBindings($constructorParameters, $this->container);

        if ($bindings !== []) {
            return $reflectionClass->newInstance(...$bindings);
        }

        // checkMe last resort
        // useful when passing string class name (subscriber|message handler)
        // which does not depend on constructor reference
        return $this->container[$reflectionClass->getName()];
    }

    /**
     * @throws RuntimeException
     */
    protected function raiseMissingAttributeException(ReflectionClass $reflectionClass): RuntimeException
    {
        throw new RuntimeException(sprintf(self::NO_ATTRIBUTE_FOUND, $this->getSupportedAttribute(), $reflectionClass->getName()));
    }

    abstract protected function process(Collection $attributes, ReflectionClass $reflectionClass, string|object $original): mixed;

    abstract protected function getSupportedAttribute(): string;
}
