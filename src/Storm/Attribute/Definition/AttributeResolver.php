<?php

declare(strict_types=1);

namespace Storm\Attribute\Definition;

use Illuminate\Container\EntryNotFoundException;
use Illuminate\Contracts\Container\Container;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use RuntimeException;
use Storm\Attribute\Definition;
use Storm\Attribute\Reference;

use function sprintf;

abstract class AttributeResolver
{
    public const NO_METHOD_FOUND = 'Method %s for class %s does not exist';

    public const REQUIRE_PUBLIC_METHOD = 'Method %s for class %s must be public';

    public const NO_PARAMETER = 'No parameter found for method %s in class %s';

    public const UNSUPPORTED_PARAMETER = 'Parameter %s for class %s is not supported';

    public const ENTRY_NOT_FOUND = 'Reference entry with %s not found in container in class %s';

    public function __construct(protected ?Container $container = null)
    {
    }

    /**
     * @param  class-string                    $attribute
     * @return array<ReflectionAttribute>|null
     */
    protected function findAttributesInClass(ReflectionClass $reflectionClass, string $attribute): ?array
    {
        $attributes = $reflectionClass->getAttributes($attribute, ReflectionAttribute::IS_INSTANCEOF);

        return $attributes === [] ? null : $attributes;
    }

    /**
     * @param array<ReflectionMethod> $reflectionMethods>
     */
    protected function getInvokableMethod(array $reflectionMethods): ?ReflectionMethod
    {
        foreach ($reflectionMethods as $reflectionMethod) {
            if ($reflectionMethod->getName() === '__invoke') {
                return $reflectionMethod;
            }
        }

        return null;
    }

    /**
     * @throws EntryNotFoundException
     */
    protected function addReferenceToDefinition(Definition $definition, ReflectionClass $reflectionClass, string $methodName): void
    {
        $references = $this->getReferenceFromConstructor($reflectionClass);

        if ($references !== null) {
            $definition->addMethod($methodName, $references);
        }
    }

    /**
     * @return array<class-string|non-empty-string>|null
     *
     * @throws EntryNotFoundException
     */
    protected function getReferenceFromConstructor(ReflectionClass $reflectionClass): ?array
    {
        $constructor = $reflectionClass->getConstructor();

        if ($constructor === null) {
            return null;
        }

        $references = $this->getStringReference($constructor->getParameters(), $reflectionClass);

        return $references !== [] ? $references : null;
    }

    /**
     * @param  array<ReflectionParameter>           $parameters
     * @return array<class-string|non-empty-string>
     *
     * @throws EntryNotFoundException
     */
    protected function getStringReference(array $parameters, ReflectionClass $reflectionClass): array
    {
        $arguments = [];

        foreach ($parameters as $parameter) {
            $reference = $parameter->getAttributes(Reference::class)[0] ?? null;

            if ($reference !== null) {
                // todo instantiate reference attribute
                $argument = $reference->getArguments()[0] ?? $this->requireNameParameter($parameter);

                if ($this->container !== null && ! $this->container->has($argument)) {
                    throw new EntryNotFoundException(sprintf(self::ENTRY_NOT_FOUND, $argument, $reflectionClass->getName()));
                }

                $arguments[] = $argument;
            }
        }

        return $arguments;
    }

    /**
     * @throws RuntimeException when parameter is not supported
     */
    protected function requireFirstParameterTypeName(ReflectionMethod $reflectionMethod): string
    {
        $parameter = $reflectionMethod->getParameters()[0] ?? null;

        if ($parameter === null) {
            throw new RuntimeException(
                sprintf(self::NO_PARAMETER, $reflectionMethod->getName(), $reflectionMethod->getDeclaringClass()->getName())
            );
        }

        return $this->requireNameParameter($parameter);
    }

    /**
     * todo pass refMethod as exception could narrow down the error the method
     *
     * @return class-string
     *
     * @throws RuntimeException when parameter is not supported
     */
    protected function requireNameParameter(ReflectionParameter $reflectionParameter): string
    {
        if ($reflectionParameter->getType() instanceof ReflectionNamedType) {
            return $reflectionParameter->getType()->getName();
        }

        throw new RuntimeException(sprintf(self::UNSUPPORTED_PARAMETER, $reflectionParameter->getName(), $reflectionParameter->getDeclaringClass()->getName()));
    }

    /**
     * @param non-empty-string $method
     *
     * @throw RuntimeException when method does not exist or is not public
     */
    protected function requirePublicMethod(ReflectionClass $reflectionClass, string $method): ReflectionMethod
    {
        if (! $reflectionClass->hasMethod($method)) {
            throw new RuntimeException(sprintf(self::NO_METHOD_FOUND, $method, $reflectionClass->getName()));
        }

        $reflectionMethod = $reflectionClass->getMethod($method);

        if (! $reflectionMethod->isPublic()) {
            throw new RuntimeException(sprintf(self::REQUIRE_PUBLIC_METHOD, $method, $reflectionClass->getName()));
        }

        return $reflectionMethod;
    }
}
