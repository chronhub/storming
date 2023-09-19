<?php

declare(strict_types=1);

namespace Storm\Attribute\Definition;

use Illuminate\Container\EntryNotFoundException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Collection;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use Storm\Attribute\Definition;
use Storm\Attribute\Exception\DefinitionException;
use Storm\Attribute\Reference;

use function sprintf;

abstract class TypeResolver
{
    public const METHOD_NOT_FOUND = 'Method %s for class %s does not exist';

    public const PUBLIC_METHOD_REQUIRED = 'Method %s for class %s must be public';

    public const PARAMETER_NOT_FOUND = 'No parameter found for method %s in class %s';

    public const PARAMETER_NOT_SUPPORTED = 'Parameter %s for class %s is not supported';

    public const ENTRY_NOT_FOUND = 'Reference entry with %s not found in container in class %s';

    public function __construct(protected ?Container $container = null)
    {
    }

    /**
     * @param Collection<ReflectionClass> $classes
     */
    abstract public function find(Collection $classes): array;

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
     * @throws DefinitionException when first parameter is not found
     */
    protected function requireFirstParameterTypeName(ReflectionMethod $reflectionMethod): string
    {
        $parameter = $reflectionMethod->getParameters()[0] ?? null;

        if ($parameter === null) {
            $this->createException(
                self::PARAMETER_NOT_FOUND,
                $reflectionMethod->getName(),
                $reflectionMethod->getDeclaringClass()->getName()
            );
        }

        return $this->requireNameParameter($parameter);
    }

    /**
     * todo pass refMethod as exception could narrow down the error the method
     *
     * @return class-string
     *
     * @throws DefinitionException when parameter is not supported
     */
    protected function requireNameParameter(ReflectionParameter $reflectionParameter): string
    {
        if ($reflectionParameter->getType() instanceof ReflectionNamedType) {
            return $reflectionParameter->getType()->getName();
        }

        throw $this->createException(
            self::PARAMETER_NOT_SUPPORTED,
            $reflectionParameter->getName(),
            $reflectionParameter->getDeclaringClass()->getName()
        );
    }

    /**
     * @param non-empty-string $method
     *
     * @throws DefinitionException when method does not exist or is not public
     */
    protected function requirePublicMethod(ReflectionClass $reflectionClass, string $method): ReflectionMethod
    {
        if (! $reflectionClass->hasMethod($method)) {
            throw $this->createException(self::METHOD_NOT_FOUND, $method, $reflectionClass->getName());
        }

        $reflectionMethod = $reflectionClass->getMethod($method);

        if (! $reflectionMethod->isPublic()) {
            throw $this->createException(self::PUBLIC_METHOD_REQUIRED, $method, $reflectionClass->getName());
        }

        return $reflectionMethod;
    }

    protected function createException(string $message, mixed ...$arguments): DefinitionException
    {
        return throw new DefinitionException(sprintf($message, ...$arguments));
    }
}
