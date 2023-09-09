<?php

declare(strict_types=1);

namespace Storm\Support\Attribute;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Collection;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use RuntimeException;

use function class_exists;

class AttributeResolver
{
    public Container $container;

    public function __construct()
    {
        $this->container = app();
    }

    /**
     * @return Collection<object>
     *
     * @throws ReflectionException
     */
    public function forClass(ReflectionClass|string $class): Collection
    {
        $reflectionClass = $class instanceof ReflectionClass ? $class : new ReflectionClass($class);

        return Collection::make($reflectionClass->getAttributes())->map(function (ReflectionAttribute $attribute) {
            if (! class_exists($attribute->getName())) {
                return null;
            }

            return $attribute->newInstance();
        })->filter();
    }

    public function newInstance(object $reflectionClass): object
    {
        if (! $reflectionClass instanceof ReflectionClass) {
            return $reflectionClass;
        }

        try {
            $parameters = $reflectionClass->getMethod('__construct')->getParameters();
        } catch (ReflectionException $e) {
            return $reflectionClass->newInstance();
        }

        $bindings = [];
        foreach ($parameters as $parameter) {
            if ($parameter->getType()->isBuiltin()) {
                // todo: support builtin types @see symfony expression
                throw new RuntimeException("Cannot resolve builtin type {$parameter->getType()->getName()}");
            }

            $reference = $parameter->getAttributes(Reference::class)[0] ?? null;
            if ($reference === null) {
                continue;
            }

            $argument = $reference->getArguments()[0] ?? $parameter->getType()->getName();

            $bindings[] = $this->container[$argument];
        }

        if ($bindings === []) {
            return $reflectionClass->newInstance();
        }

        return $reflectionClass->newInstance(...$bindings);
    }

    public function setContainer(Container $container): void
    {
        $this->container = $container;
    }
}
