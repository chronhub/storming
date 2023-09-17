<?php

declare(strict_types=1);

namespace Storm\Attribute\Definition;

use Illuminate\Support\Collection;
use ReflectionAttribute;
use ReflectionClass;
use RuntimeException;
use Storm\Attribute\ReflectionUtil;
use Storm\Reporter\Attribute\AsSubscriber;

final class SubscriberResolver extends AttributeResolver
{
    public function find(Collection $classes): Collection
    {
        return $classes->map(function (ReflectionClass $reflectionClass) {
            $attributes = $this->findAttributesInClass($reflectionClass, AsSubscriber::class);

            if ($attributes === null) {
                return null;
            }

            return $this->findInClass($reflectionClass, $attributes);
        })->filter();
    }

    /**
     * @param array<ReflectionAttribute> $attributes>
     */
    private function findInClass(ReflectionClass $reflectionClass, array $attributes): array
    {
        $definitions = [];

        foreach ($attributes as $attribute) {
            /** @var AsSubscriber $asSubscriber */
            $asSubscriber = $attribute->newInstance();

            $definitions[] = $definition = new SubscriberDefinition(
                $reflectionClass->getName(),
                $asSubscriber->eventName,
                $asSubscriber->priority,
            );

            $methodName = $asSubscriber->method;
            $reflectionMethod = ReflectionUtil::requirePublicMethod($reflectionClass, $methodName);
            $parameters = $reflectionMethod->getParameters();

            if ($parameters !== []) {
                throw new RuntimeException("Method $methodName for class {$reflectionClass->getName()} must not have parameters");
            }

            $definition->addMethod($methodName);

            $arguments = $this->getReferenceFromConstructor($reflectionClass);

            if ($arguments !== null) {
                $definition->addMethod('__construct', $arguments);
            }
        }

        return $definitions;
    }
}
