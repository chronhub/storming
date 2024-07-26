<?php

declare(strict_types=1);

namespace Storm\Annotation;

use Illuminate\Support\Collection;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;

abstract class AnnotationLoader
{
    protected function findAttributesInClass(ReflectionClass $reflectionClass, string $attribute): void
    {
        $attributes = $this->attributesInClass($reflectionClass, $attribute);

        if ($attributes->isEmpty()) {
            return;
        }

        $this->processAttributes($reflectionClass, null, $attributes);
    }

    protected function findAttributesInMethods(ReflectionClass $reflectionClass, string $attribute): void
    {
        $methods = $this->attributesInMethods($reflectionClass, $attribute);

        $methods->each(function (array $reflection): void {
            [$reflectionClass, $reflectionMethod, $attributes] = $reflection;

            if ($attributes->isNotEmpty()) {
                $this->processAttributes($reflectionClass, $reflectionMethod, $attributes);
            }
        });
    }

    protected function determineMethodName(?string $methodName, ?ReflectionMethod $reflectionMethod): string
    {
        return match (true) {
            $methodName !== null => $methodName,
            $reflectionMethod !== null => $reflectionMethod->getName(),
            default => '__invoke',
        };
    }

    abstract protected function processAttributes(ReflectionClass $reflectionClass, ?ReflectionMethod $reflectionMethod, Collection $attributes): void;

    /**
     * @return Collection<ReflectionAttribute>
     */
    protected function attributesInClass(ReflectionClass $reflectionClass, string $attribute): Collection
    {
        return collect($reflectionClass->getAttributes($attribute, ReflectionAttribute::IS_INSTANCEOF));
    }

    /**
     * @return Collection<array{0: ReflectionClass, 1: ReflectionMethod, 2: Collection<ReflectionAttribute|empty>}>
     */
    protected function attributesInMethods(ReflectionClass $reflectionClass, string $attribute): Collection
    {
        return collect($reflectionClass->getMethods())->map(
            fn (ReflectionMethod $reflectionMethod): array => [
                $reflectionClass,
                $reflectionMethod,
                collect($reflectionMethod->getAttributes($attribute, ReflectionAttribute::IS_INSTANCEOF)),
            ]
        );
    }
}
