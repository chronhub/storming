<?php

declare(strict_types=1);

namespace Storm\Attribute\Definition;

use Storm\Attribute\Exception\DefinitionException;

use function array_key_exists;

final class DeclarationScopeValidation extends AbstractMessageHandlerValidation
{
    public const SCOPE_NOT_FOUND = 'Invalid scope %s for message handler %s::%s';

    public const SCOPE_NOT_UNIQUE = 'Message %s has handlers with the "Unique" scope';

    public const SCOPE_NOT_IN_CLASS = 'Message %s has handlers with different classes for "BelongsToClass" scope';

    public const SCOPE_INCOMPATIBLE = 'Message %s has handlers with the "BelongsToClass" and "BelongsToMany" scopes';

    /**
     * Validate scope for message declaration per message name
     *
     * @throws DefinitionException when scope is not found
     * @throws DefinitionException when handler is found more than once with unique scope
     * @throws DefinitionException when multiple handlers with different classes are found for BelongsToClass scope
     * @throws DefinitionException when multiple handlers with BelongsToClass and BelongsToMany scopes are found
     */
    public function validate(array $map): void
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
                throw $this->createException(self::SCOPE_INCOMPATIBLE, $messageName);
            }
        }
    }
}
