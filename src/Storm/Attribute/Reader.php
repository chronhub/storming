<?php

declare(strict_types=1);

namespace Storm\Attribute;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Collection;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;

abstract class Reader
{
    public function __construct(protected readonly Container $container)
    {
    }

    /**
     * Read attributes of a specific class.
     *
     * @param  class-string|object $stringOrObject
     * @return Collection<object>
     *
     * @throws ReflectionException
     */
    protected function readAttribute(string|object $stringOrObject, string $attributeClass): Collection
    {
        $reflectionClass = ReflectionUtil::createReflectionClass($stringOrObject);

        return ReflectionUtil::createAttributeCollection(
            $reflectionClass->getAttributes($attributeClass, ReflectionAttribute::IS_INSTANCEOF)
        );
    }

    /**
     * Create a new instance of a class considering constructor dependencies.
     *
     * @throws ReflectionException
     */
    protected function createInstance(object $reflectionClass): object
    {
        if (! $reflectionClass instanceof ReflectionClass) {
            return $reflectionClass;
        }

        $constructorParameters = ReflectionUtil::getConstructorParameters($reflectionClass);

        if ($constructorParameters === []) {
            return $reflectionClass->newInstance();
        }

        $bindings = ReflectionUtil::getReferenceBindings($constructorParameters, $this->container);

        return $reflectionClass->newInstance(...$bindings);
    }
}
