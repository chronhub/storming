<?php

declare(strict_types=1);

namespace Storm\Attribute;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use RuntimeException;

use function class_exists;
use function is_string;
use function sprintf;

class ReflectionUtil
{
    public const InvalidParameterType = 'Unsupported parameter %s in class %s';

    /**
     * @param class-string|object $class
     *
     * @throws ReflectionException
     * @throws InvalidArgumentException when the class string does not exist
     */
    public static function createReflectionClass(string|object $class): ReflectionClass
    {
        if (is_string($class) && ! class_exists($class)) {
            throw new InvalidArgumentException("Class $class does not exist");
        }

        return $class instanceof ReflectionClass ? $class : new ReflectionClass($class);
    }

    /**
     * @param  class-string|object             $className
     * @param  class-string                    $attributeClass
     * @return Collection<ReflectionAttribute>
     *
     * @throws ReflectionException
     */
    public static function getAttributesInstancesForClass(string|object $className, string $attributeClass): Collection
    {
        $reflectionClass = self::createReflectionClass($className);

        return Collection::wrap($reflectionClass->getAttributes($attributeClass, ReflectionAttribute::IS_INSTANCEOF))
            ->map(function (ReflectionAttribute $attribute) {
                if (! class_exists($attribute->getName())) {
                    return null;
                }

                return $attribute->newInstance();
            })
            ->filter();
    }

    /**
     * @return array<ReflectionParameter|empty>
     */
    public static function getConstructorParameters(ReflectionClass $reflectionClass): array
    {
        $constructor = $reflectionClass->getConstructor();

        return $constructor ? $constructor->getParameters() : [];
    }

    /**
     * @param array<ReflectionMethod> $reflectionMethods>
     */
    public static function getInvokableMethod(array $reflectionMethods): ?ReflectionMethod
    {
        foreach ($reflectionMethods as $reflectionMethod) {
            if ($reflectionMethod->getName() === '__invoke') {
                return $reflectionMethod;
            }
        }

        return null;
    }

    public static function requirePublicInvokableMethod(array $reflectionMethods): ReflectionMethod
    {
        $invokableMethod = self::getInvokableMethod($reflectionMethods);

        if ($invokableMethod === null) {
            throw new RuntimeException("invokable method is mandatory in class {$reflectionMethods[0]->getDeclaringClass()->getName()}");
        }

        return $invokableMethod;
    }

    /**
     * @param non-empty-string $method
     *
     * @throw RuntimeException when method does not exist or is not public
     */
    public static function requirePublicMethod(ReflectionClass $reflectionClass, string $method): ReflectionMethod
    {
        if (! $reflectionClass->hasMethod($method)) {
            throw new RuntimeException("Method $method for class {$reflectionClass->getName()} does not exist");
        }

        $reflectionMethod = $reflectionClass->getMethod($method);

        if (! $reflectionMethod->isPublic()) {
            throw new RuntimeException("Method $method for class {$reflectionClass->getName()} must be public");
        }

        return $reflectionMethod;
    }

    /**
     * Find methods which implements the attribute.
     *
     * @param  class-string            $attribute
     * @return array<ReflectionMethod> return ReflectionMethods that contains the attribute
     */
    public static function getPublicMethodsByAttribute(ReflectionClass $reflectionClass, string $attribute): array
    {
        $reflectionMethods = $reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC);

        $found = [];

        foreach ($reflectionMethods as $reflectionMethod) {
            if ($reflectionMethod->isConstructor()) {
                continue;
            }

            $attributes = $reflectionMethod->getAttributes($attribute, ReflectionAttribute::IS_INSTANCEOF);

            if ($attributes !== []) {
                $found[] = $reflectionMethod;
            }
        }

        return $found;
    }

    /**
     * @param array<ReflectionParameter> $parameters>
     */
    public static function getReferenceBindings(array $parameters, Container $container): array
    {
        $bindings = [];

        foreach ($parameters as $parameter) {
            $reference = $parameter->getAttributes(Reference::class)[0] ?? null;

            if ($reference !== null) {
                $argument = $reference->getArguments()[0] ?? self::getParameterTypeName($parameter);
                $bindings[] = $container[$argument];
            }
        }

        return $bindings;
    }

    public static function requireFirstParameterTypeName(ReflectionMethod $reflectionMethod): string
    {
        $parameter = $reflectionMethod->getParameters()[0] ?? null;

        if ($parameter === null) {
            throw new RuntimeException("Missing parameter for message handler {$reflectionMethod->getName()} in class {$reflectionMethod->getDeclaringClass()->getName()}");
        }

        return self::getParameterTypeName($parameter);
    }

    /**
     * Get the type name for a parameter's type hint.
     *
     * @throws RuntimeException when the parameter type is invalid or not supported
     */
    public static function getParameterTypeName(ReflectionParameter $parameter): string
    {
        $type = $parameter->getType();

        if ($type instanceof ReflectionNamedType) {
            return $type->getName();
        }

        throw new RuntimeException(
            sprintf(
                self::InvalidParameterType,
                $parameter->getName(),
                $parameter->getDeclaringClass()->getName()
            )
        );

    }
}
