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

use function array_column;
use function array_count_values;
use function array_filter;
use function array_key_exists;
use function count;
use function usort;

final class MessageHandlerResolver extends TypeResolver
{
    public const ATTRIBUTE_NOT_REPEATABLE = '#AsMessageHandler attribute is only repeatable for target method in %s';

    public const METHOD_NOT_ALLOWED = 'Invokable method is disallowed when using attribute targeted method for class %s';

    public const MESSAGE_HAS_MULTIPLE_HANDLERS_WITH_SAME_PRIORITY = 'Message %s has multiple handlers with the same priority';

    public const SCOPE_NOT_FOUND = 'Invalid scope %s for message handler %s::%s';

    public const SCOPE_NOT_UNIQUE = 'Message %s has multiple handlers with the "Unique" scope';

    public const SCOPE_NOT_IN_CLASS = 'Message $messageName has handlers with different classes for "BelongsToClass" scope';

    public const MESSAGE_HAS_MULTIPLE_HANDLERS_WITH_DIFFERENT_CLASSES = 'Message %s has multiple handlers with the "BelongsToClass" scope, but they have different classes';

    public function find(Collection $classes): array
    {
        $definitions = $this->makeDefinitions($classes);

        $map = $this->gatherDefinitionsByMessageName($definitions->toArray());

        $this->validateMessageDeclarationScope($map);
        $this->validateUniquePriorityWhenManyHandlers($map);

        return $this->sortMessageHandlersByPriority($map);
    }

    private function makeDefinitions(Collection $classes): Collection
    {
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
     * Find @see AsMessageHandler attribute in methods class
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

        foreach ($reflectionMethods as $reflectionMethod) {
            if ($reflectionMethod->isConstructor()) {
                continue;
            }

            $attributes = $reflectionMethod->getAttributes(AsMessageHandler::class);

            if ($attributes === []) {
                continue;
            }

            if ($this->getInvokableMethod($reflectionMethods)) {
                throw $this->createException(self::METHOD_NOT_ALLOWED, $reflectionClass->getName());
            }

            $definition = $this->getDefinition($reflectionClass, $attributes[0]->newInstance(), $reflectionMethod);

            $this->addReferenceToDefinition($definition, $reflectionClass, '__construct');

            $definitions[] = $definition;
        }

        return $definitions === [] ? null : $definitions;
    }

    /**
     * @throws DefinitionException when method not found
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

    /**
     * Validate scope for message declaration per message name
     *
     * @throws DefinitionException when scope is not found
     * @throws DefinitionException when multiple handlers with different classes are found for BelongsToClass scope
     * @throws DefinitionException when multiple handlers with unique scope are found
     */
    private function validateMessageDeclarationScope(array $map): void
    {
        foreach ($map as $messageName => $handlers) {
            $counts = [
                MessageDeclarationScope::Unique->value => 0,
                MessageDeclarationScope::BelongsToClass->value => 0,
                MessageDeclarationScope::BelongsToMany->value => 0,
            ];

            $classForBelongsToClass = null;

            foreach ($handlers as $info) {
                $scope = $info['scope'];

                if (! array_key_exists($scope, $counts)) {
                    throw $this->createException(self::SCOPE_NOT_FOUND, $scope, $info['class'], $info['method']);
                }

                $counts[$scope]++;

                if ($scope === MessageDeclarationScope::BelongsToClass->value) {
                    if ($classForBelongsToClass === null) {
                        $classForBelongsToClass = $info['class'];
                    } elseif ($info['class'] !== $classForBelongsToClass) {
                        throw $this->createException(self::SCOPE_NOT_IN_CLASS, $messageName);
                    }
                }
            }

            if ($counts[MessageDeclarationScope::Unique->value] > 1) {
                throw $this->createException(self::SCOPE_NOT_UNIQUE, $messageName);
            }

            if ($counts[MessageDeclarationScope::BelongsToClass->value] > 1 && $classForBelongsToClass === null) {
                throw $this->createException(self::MESSAGE_HAS_MULTIPLE_HANDLERS_WITH_DIFFERENT_CLASSES, $messageName);
            }
        }
    }

    /**
     * @throws DefinitionException When a message has multiple handlers with the same priority
     */
    private function validateUniquePriorityWhenManyHandlers(array $map): void
    {
        foreach ($map as $messageName => $handlers) {
            if (count($handlers) < 2) {
                continue;
            }

            $duplicates = array_filter(
                array_count_values(array_column($handlers, 'priority')),
                fn (int $count) => $count > 1
            );

            if ($duplicates !== []) {
                throw $this->createException(self::MESSAGE_HAS_MULTIPLE_HANDLERS_WITH_SAME_PRIORITY, $messageName);
            }
        }
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
}
