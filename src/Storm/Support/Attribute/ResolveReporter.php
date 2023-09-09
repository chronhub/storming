<?php

declare(strict_types=1);

namespace Storm\Support\Attribute;

use ReflectionClass;
use RuntimeException;
use Storm\Contract\Reporter\Reporter;
use Storm\Reporter\Attribute\AsReporter;
use Storm\Reporter\Subscriber\FilterMessage;
use Storm\Reporter\Subscriber\NameReporter;

use function is_string;

readonly class ResolveReporter
{
    public function __construct(private AttributeResolver $attributeResolver)
    {
    }

    public function resolve(string $className): Reporter
    {
        $reflectionClass = new ReflectionClass($className);

        return $this->attributeResolver->forClass($reflectionClass)
            ->filter(function ($attribute) {
                return $attribute instanceof AsReporter;
            })->whenEmpty(function () use ($className) {
                throw new RuntimeException("Missing #AsReporter attribute for class $className");
            })
            ->map(function (AsReporter $attribute) use ($reflectionClass) {
                $instance = $this->attributeResolver->newInstance($reflectionClass);

                $filter = $attribute->filter;

                if (is_string($filter)) {
                    $filter = $this->attributeResolver->container[$filter];
                }

                $instance->subscribe(
                    new NameReporter($attribute->name ?? $reflectionClass->getName()),
                    new FilterMessage($filter)
                );

                return $instance;

            })->first();
    }
}
