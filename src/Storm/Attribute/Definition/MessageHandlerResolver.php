<?php

declare(strict_types=1);

namespace Storm\Attribute\Definition;

use Illuminate\Container\EntryNotFoundException;
use Illuminate\Support\Collection;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use Storm\Attribute\Exception\DefinitionException;
use Storm\Reporter\Attribute\AsMessageHandler;

use function count;
use function usort;

final class MessageHandlerResolver extends TypeResolver
{
    public const ATTRIBUTE_NOT_REPEATABLE = '#AsMessageHandler attribute is only repeatable for target class in %s';

    public const METHOD_NOT_ALLOWED = 'Invokable method is disallowed when targeted many methods for class %s';

    public function find(Collection $classes): array
    {
        $definitions = $this->makeDefinitions($classes);

        $map = $this->gatherDefinitionsByMessageName($definitions->toArray());

        foreach ($this->getValidators() as $validator) {
            $validator->validate($map);
        }

        return $this->sortMessageHandlersByPriority($map);
    }

    private function makeDefinitions(Collection $classes): Collection
    {
        return $classes->map(function (ReflectionClass $reflectionClass) {
            $attributes = $this->findAttributesInClass($reflectionClass, AsMessageHandler::class);

            if ($attributes) {
                return $this->findInClass($reflectionClass, $attributes);
            }

            return $this->findInMethods($reflectionClass);
        })->filter();
    }

    /**
     * Find attribute in class
     *
     * Only one attribute is allowed when class is targeted
     * First method parameter must be the message instance
     *
     * @param array<ReflectionAttribute> $attributes
     *
     * @throws DefinitionException    when attribute is repeated in target class
     * @throws DefinitionException    when first parameter aka message is not found in method
     * @throws EntryNotFoundException when reference is not found in container
     */
    private function findInClass(ReflectionClass $reflectionClass, array $attributes): MessageHandlerDefinition
    {
        if (count($attributes) > 1) {
            throw $this->createException(self::ATTRIBUTE_NOT_REPEATABLE, $reflectionClass->getName());
        }

        $definition = $this->getDefinition($reflectionClass, $attributes[0]->newInstance(), null);

        $this->addReferenceToDefinition($definition, $reflectionClass, '__construct');

        return $definition;
    }

    /**
     * Find @see AsMessageHandler attribute in class methods
     *
     * @throws DefinitionException    when invokable method is found for many handlers
     * @throws DefinitionException    when first parameter aka message is not found in method
     * @throws EntryNotFoundException when reference is not found in container
     */
    private function findInMethods(ReflectionClass $reflectionClass): ?array
    {
        $reflectionMethods = $reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC);

        if ($reflectionMethods === []) {
            return null;
        }

        $definitions = [];

        $count = 0;
        foreach ($reflectionMethods as $reflectionMethod) {
            if ($reflectionMethod->isConstructor()) {
                continue;
            }

            $attributes = $reflectionMethod->getAttributes(AsMessageHandler::class);

            if ($attributes === []) {
                continue;
            }

            if ($count > 1 && $this->getInvokableMethod($reflectionMethods)) {
                throw $this->createException(self::METHOD_NOT_ALLOWED, $reflectionClass->getName());
            }

            $definition = $this->getDefinition($reflectionClass, $attributes[0]->newInstance(), $reflectionMethod);

            $this->addReferenceToDefinition($definition, $reflectionClass, '__construct');

            $definitions[] = $definition;

            $count++;
        }

        return $definitions === [] ? null : $definitions;
    }

    /**
     * @throws DefinitionException when method not found or not public
     * @throws DefinitionException when first parameter aka message is not found in method
     */
    private function getDefinition(
        ReflectionClass $reflectionClass,
        AsMessageHandler $attribute,
        ?ReflectionMethod $reflectionMethod
    ): MessageHandlerDefinition {

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

    private function gatherDefinitionsByMessageName(array $definitions): array
    {
        $map = [];

        foreach ($definitions as $definition) {
            if ($definition instanceof MessageHandlerDefinition) {
                $map[$definition->messageName][] = $definition->jsonSerialize();
            } else {
                foreach ($definition as $def) {
                    $map[$def->messageName][] = $def->jsonSerialize();
                }
            }
        }

        return $map;
    }

    private function sortMessageHandlersByPriority(array $map): array
    {
        foreach ($map as &$handlers) {
            usort($handlers, function ($a, $b) {
                return $a['priority'] <=> $b['priority'];
            });
        }

        return $map;
    }

    /**
     * @return array<AbstractMessageHandlerValidation>
     */
    private function getValidators(): array
    {
        return [
            new DeclarationScopeValidation(),
            new UniquePriorityValidation(),
        ];
    }
}
