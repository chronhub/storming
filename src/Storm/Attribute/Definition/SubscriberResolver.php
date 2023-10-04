<?php

declare(strict_types=1);

namespace Storm\Attribute\Definition;

use Illuminate\Container\EntryNotFoundException;
use Illuminate\Support\Collection;
use ReflectionAttribute;
use ReflectionClass;
use Storm\Attribute\Exception\DefinitionException;
use Storm\Reporter\Attribute\AsSubscriber;

final class SubscriberResolver extends TypeResolver
{
    public function find(Collection $classes): array
    {
        return $classes->map(function (ReflectionClass $reflectionClass) {
            $attributes = $this->findAttributesInClass($reflectionClass, AsSubscriber::class);

            if ($attributes === null) {
                return null;
            }

            return $this->findInClass($reflectionClass, $attributes);
        })->filter()->jsonSerialize();
    }

    /**
     * @param array<ReflectionAttribute> $attributes>
     *
     * @throws EntryNotFoundException
     */
    private function findInClass(ReflectionClass $reflectionClass, array $attributes): SubscriberDefinition
    {
        $definition = new SubscriberDefinition($reflectionClass->getName());

        foreach ($attributes as $attribute) {
            /** @var AsSubscriber $asSubscriber */
            $asSubscriber = $attribute->newInstance();
            $methodName = $asSubscriber->method;

            $this->assertMethodHasNoParameter($reflectionClass, $methodName);

            $definition->addEvent($asSubscriber->eventName, $asSubscriber->priority, $methodName);

            $definition->addMethod($methodName);

            $arguments = $this->getReferenceFromConstructor($reflectionClass);

            // checkMe by now we seem to accept references parameters only in constructor
            if ($arguments !== null) {
                $definition->addMethod('__construct', $arguments);
            }
        }

        return $definition;
    }

    /**
     * checkMe allow later to use constructor parameters and public method parameters
     * could be useful when a subscriber provide many events but require different dependencies
     *
     * @throws DefinitionException when method has parameters
     */
    private function assertMethodHasNoParameter(ReflectionClass $reflectionClass, string $methodName): void
    {
        $reflectionMethod = $this->requirePublicMethod($reflectionClass, $methodName);

        $parameters = $reflectionMethod->getParameters();

        if ($parameters !== []) {
            throw new DefinitionException("Method $methodName for class {$reflectionClass->getName()} must not have parameters");
        }
    }
}
