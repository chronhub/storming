<?php

declare(strict_types=1);

namespace Storm\Support\Attribute;

use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionException;
use RuntimeException;
use Storm\Contract\Tracker\Listener;
use Storm\Reporter\Attribute\AsSubscriber;
use Storm\Tracker\GenericListener;

use function is_object;
use function is_string;

readonly class ResolveSubscriber
{
    public function __construct(private AttributeResolver $attributeResolver)
    {
    }

    /**
     * @return Collection<Listener>
     *
     * @throws ReflectionException
     */
    public function resolve(string|object $class): Collection
    {
        $className = is_string($class) ? $class : $class::class;

        $reflectionClass = new ReflectionClass($class);

        return $this->attributeResolver->forClass($reflectionClass)
            ->filter(function ($attribute) {
                return $attribute instanceof AsSubscriber;
            })->whenEmpty(function () use ($className) {
                throw new RuntimeException("Missing #AsSubscriber attribute for class $className");
            })->map(function (AsSubscriber $attribute) use ($reflectionClass, $class) {
                $method = $attribute->method ?? '__invoke';

                $parameters = $reflectionClass->getMethod($method)->getParameters();
                if ($parameters !== []) {
                    throw new RuntimeException("Method $method for class {$reflectionClass->getName()} must not have parameters");
                }

                // in case we already init attribute class
                $instance = is_object($class) ? $class : $this->attributeResolver->newInstance($reflectionClass);

                return new GenericListener(
                    $attribute->eventName,
                    $instance->$method()(...),
                    $attribute->priority,
                );
            });
    }
}
