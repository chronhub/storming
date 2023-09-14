<?php

declare(strict_types=1);

namespace Storm\Attribute;

use ReflectionAttribute;
use ReflectionClass;

final readonly class AttributeFinder
{
    public function __construct(public string $attributeClass)
    {
    }

    public function findAttributesInClass(ReflectionClass $reflectionClass): ?string
    {
        $attributes = $reflectionClass->getAttributes($this->attributeClass, ReflectionAttribute::IS_INSTANCEOF);

        return $attributes === [] ? null : $this->attributeClass;
    }

    public function findAttributesInMethod(ReflectionClass $reflectionClass): array
    {
        $reflectionMethods = $reflectionClass->getMethods();

        $found = [];
        foreach ($reflectionMethods as $reflectionMethod) {
            $attributes = $reflectionMethod->getAttributes($this->attributeClass, ReflectionAttribute::IS_INSTANCEOF);

            if ($attributes !== []) {
                $found[] = [$reflectionMethod->getName() => $this->attributeClass]; //fixMe instantiate attribute?
            }
        }

        return $found;
    }
}
