<?php

declare(strict_types=1);

namespace Storm\Attribute\Definition;

use Illuminate\Container\EntryNotFoundException;
use Illuminate\Support\Collection;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use Storm\Reporter\Attribute\AsMessageHandler;

use function count;

final class MessageHandlerResolver extends AttributeResolver
{
    public function find(Collection $classes): Collection
    {
        // need a better strategy to organize message handlers
        // as is, we allow message name to be handled by class and methods
        // declared in another class

        return $classes->map(function (ReflectionClass $reflectionClass) {
            $attributes = $this->findAttributesInClass($reflectionClass, AsMessageHandler::class);

            if ($attributes) {
                return $this->findInClass($reflectionClass, $attributes);
            }

            if ($definitions = $this->findInMethods($reflectionClass)) {
                return $definitions;
            }

            return null;
        })->filter();
    }

    /**
     * Find attribute in class
     *
     * Only one attribute is allowed per class
     * First method parameter must be the message instance
     *
     * @param array<ReflectionAttribute> $attributes
     *
     * @throws RuntimeException       when attribute is repeated in target class
     * @throws EntryNotFoundException when reference is not found in container
     */
    private function findInClass(ReflectionClass $reflectionClass, array $attributes): MessageHandlerDefinition
    {
        if (count($attributes) > 1) {
            throw new RuntimeException("#AsMessageHandler attribute is only repeatable for target method in {$reflectionClass->getName()}");
        }

        $definition = $this->getDefinition($reflectionClass, $attributes[0], null);

        $this->addReferenceToDefinition($definition, $reflectionClass, '__construct');

        return $definition;
    }

    /**
     * Find AsMessageHandler attribute in methods class
     *
     * No invokable method is allowed as we turn message handler into callable,
     * it could lead to unexpected behavior
     * First parameter for each method must be the message instance
     *
     * @return array<MessageHandlerDefinition>|null
     *
     * @throws RuntimeException       when invokable method is found in class
     * @throws EntryNotFoundException when reference is not found in container
     */
    private function findInMethods(ReflectionClass $reflectionClass): ?array
    {
        $reflectionMethods = $reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC);

        if ($reflectionMethods === []) {
            return null;
        }

        $definitions = [];

        foreach ($reflectionMethods as $reflectionMethod) {
            if ($reflectionMethod->isConstructor()) {
                continue;
            }

            $attributes = $reflectionMethod->getAttributes(AsMessageHandler::class);

            if ($attributes === []) {
                continue;
            }

            if ($this->getInvokableMethod($reflectionMethods)) {
                throw new RuntimeException("Invokable method is disallowed when using attribute targeted method for class {$reflectionClass->getName()}");
            }

            $definition = $this->getDefinition($reflectionClass, $attributes[0], $reflectionMethod);

            $this->addReferenceToDefinition($definition, $reflectionClass, '__construct');

            $definitions[] = $definition;
        }

        return $definitions === [] ? null : $definitions;
    }

    private function getDefinition(
        ReflectionClass $reflectionClass,
        ReflectionAttribute $reflectionAttribute,
        ?ReflectionMethod $reflectionMethod
    ): MessageHandlerDefinition {
        /** @var AsMessageHandler $attribute */
        $attribute = $reflectionAttribute->newInstance();

        if ($reflectionMethod === null) {
            $reflectionMethod = $this->requirePublicMethod($reflectionClass, $attribute->method);
        }

        $parameterMessage = $this->requireFirstParameterTypeName($reflectionMethod);

        return new MessageHandlerDefinition(
            $reflectionClass->getName(),
            $parameterMessage,
            $reflectionMethod->getName(),
            $attribute->priority,
            $attribute->scope
        );
    }
}
