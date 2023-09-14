<?php

declare(strict_types=1);

namespace Storm\Support\Attribute;

use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use RuntimeException;
use Storm\Attribute\ReflectionUtil;
use Storm\Attribute\TypeResolver;
use Storm\Reporter\Attribute\AsMessageHandler;

use function count;
use function sprintf;

final class MessageHandlerResolver extends TypeResolver
{
    public const ATTRIBUTE_NAME = AsMessageHandler::class;

    public const NO_PARAMETER_EXCEPTION = 'No parameters found for method %s for class %s';

    public const UNSUPPORTED_PARAMETER_EXCEPTION = 'Parameter %s for class %s is not supported';

    public function process(Collection $attributes, ReflectionClass $reflectionClass, string|object $original): MessageHandlerInstance
    {
        if ($attributes->isNotEmpty() && $instance = $this->findAttributeInClass($reflectionClass)) {
            return $instance;
        }

        if ($instance = $this->findAttributeInMethods($reflectionClass)) {
            return $instance;
        }

        throw $this->raiseMissingAttributeException($reflectionClass);
    }

    public function getSupportedAttribute(): string
    {
        return self::ATTRIBUTE_NAME;
    }

    /**
     * Find AsMessageHandler attribute in class
     *
     * Only one attribute is allowed per class
     * An invokable method is required
     * First method parameter must be the message instance
     *
     * @throws ReflectionException
     */
    private function findAttributeInClass(ReflectionClass $reflectionClass): ?MessageHandlerInstance
    {
        $attributes = $reflectionClass->getAttributes(self::ATTRIBUTE_NAME);

        if ($attributes !== []) {
            if (count($attributes) > 1) {
                throw new RuntimeException("Only one #AsMessageHandler attribute is allowed per class for {$reflectionClass->getName()}");
            }

            $methods = $reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC);

            $invokableMethod = ReflectionUtil::getInvokableMethod($methods);

            if ($invokableMethod === null) {
                throw new RuntimeException("No invokable method found for class {$reflectionClass->getName()}");
            }

            $firstParameter = $invokableMethod->getParameters()[0] ?? null;

            if ($firstParameter === null) {
                throw new RuntimeException(sprintf(self::NO_PARAMETER_EXCEPTION, $invokableMethod->getName(), $reflectionClass->getName()));
            }

            return new MessageHandlerInstance(
                $this->getNameParameter($firstParameter),
                $this->createInstance($reflectionClass),
                $invokableMethod->getName()
            );
        }

        return null;
    }

    /**
     * Find AsMessageHandler attribute in methods class
     *
     * No invokable method is allowed as we turn message handler into callable,
     * it could lead to unexpected behavior
     *
     * First parameter for each method must be the message instance
     *
     * @throws ReflectionException
     */
    private function findAttributeInMethods(ReflectionClass $reflectionClass): ?MessageHandlerInstance
    {
        $reflectionMethods = $reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC);

        if (ReflectionUtil::getInvokableMethod($reflectionMethods) !== null) {
            throw new RuntimeException("Invokable method is disallowed when using attribute targeted method for class {$reflectionClass->getName()}");
        }

        foreach ($reflectionMethods as $reflectionMethod) {
            if ($reflectionMethod->isConstructor()) {
                continue;
            }

            $attributes = $reflectionMethod->getAttributes(AsMessageHandler::class);

            if ($attributes === []) {
                continue;
            }

            $firstParameter = $reflectionMethod->getParameters()[0] ?? null;

            if ($firstParameter === null) {
                throw new RuntimeException(sprintf(self::NO_PARAMETER_EXCEPTION, $reflectionMethod->getName(), $reflectionClass->getName()));
            }

            return new MessageHandlerInstance(
                $this->getNameParameter($firstParameter),
                $this->createInstance($reflectionClass),
                $reflectionMethod->getName()
            );
        }

        return null;
    }

    private function getNameParameter(ReflectionParameter $reflectionParameter): string
    {
        if ($reflectionParameter->getType() instanceof ReflectionNamedType) {
            return $reflectionParameter->getType()->getName();
        }

        throw new RuntimeException(sprintf(self::UNSUPPORTED_PARAMETER_EXCEPTION, $reflectionParameter->getName(), $reflectionParameter->getDeclaringClass()->getName()));
    }
}
