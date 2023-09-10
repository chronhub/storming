<?php

declare(strict_types=1);

namespace Storm\Attribute;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionParameter;
use RuntimeException;

use function class_exists;
use function is_string;
use function sprintf;

class ReflectionUtil
{
    public const MissingParameterType = 'Missing parameter type %s in class %s';

    public const InvalidParameterType = 'Unable to resolve union or intersection type for parameter %s in class %s, use reference attribute to resolve it';

    public const UnsupportedBuiltInType = 'Unsupported built-in type for parameter %s in class %s';

    /**
     * @param  array<ReflectionAttribute> $attributes
     * @return Collection<object>
     */
    public static function createAttributeCollection(array $attributes): Collection
    {
        return Collection::make($attributes)
            ->map(fn (ReflectionAttribute $attribute) => $attribute->newInstance())
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

    /**
     * Get the type name for a parameter's type hint.
     *
     * @throws RuntimeException when the parameter type is invalid or not supported
     */
    protected static function getParameterTypeName(ReflectionParameter $parameter): string
    {
        $type = $parameter->getType();

        if ($type instanceof ReflectionNamedType) {
            if (! $type->isBuiltin()) {
                return $type->getName();
            }

            throw self::createParameterException(self::UnsupportedBuiltInType, $parameter);
        }

        if ($type === null) {
            throw self::createParameterException(self::MissingParameterType, $parameter);
        }

        throw self::createParameterException(self::InvalidParameterType, $parameter);
    }

    /**
     * Create a parameter-related exception.
     */
    protected static function createParameterException(string $message, ReflectionParameter $reflection): RuntimeException
    {
        return new RuntimeException(
            sprintf(
                $message,
                $reflection->getName(),
                $reflection->getDeclaringClass()->getName()
            )
        );
    }

    /**
     * @param class-string|object $class
     *
     * @throws ReflectionException
     * @throws InvalidArgumentException when the string class does not exist
     */
    public static function createReflectionClass(string|object $class): ReflectionClass
    {
        if (is_string($class) && ! class_exists($class)) {
            throw new InvalidArgumentException("Class $class does not exist");
        }

        return $class instanceof ReflectionClass ? $class : new ReflectionClass($class);
    }
}
