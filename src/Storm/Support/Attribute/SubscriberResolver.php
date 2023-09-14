<?php

declare(strict_types=1);

namespace Storm\Support\Attribute;

use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionException;
use RuntimeException;
use Storm\Attribute\ReflectionUtil;
use Storm\Attribute\TypeResolver;
use Storm\Contract\Tracker\Listener;
use Storm\Reporter\Attribute\AsSubscriber;
use Storm\Tracker\GenericListener;

use function is_object;

final class SubscriberResolver extends TypeResolver
{
    public const ATTRIBUTE_NAME = AsSubscriber::class;

    /**
     * @return Collection<Listener>
     *
     * @throws ReflectionException
     */
    public function process(Collection $attributes, ReflectionClass $reflectionClass, string|object $original): Collection
    {
        return $attributes
            ->whenEmpty(function () use ($reflectionClass) {
                $this->raiseMissingAttributeException($reflectionClass);
            })
            ->map(function (AsSubscriber $attribute) use ($reflectionClass, $original) {
                $methodName = $attribute->method;

                $reflectionMethod = ReflectionUtil::requirePublicMethod($reflectionClass, $methodName);

                $parameters = $reflectionMethod->getParameters();

                if ($parameters !== []) {
                    throw new RuntimeException("Method $methodName for class {$reflectionClass->getName()} must not have parameters");
                }

                // in some cases, we can already have an instance
                $instance = is_object($original) ? $original : $this->createInstance($reflectionClass);

                return new GenericListener($attribute->eventName, $instance->$methodName()(...), $attribute->priority);
            });
    }

    public function getSupportedAttribute(): string
    {
        return self::ATTRIBUTE_NAME;
    }
}
