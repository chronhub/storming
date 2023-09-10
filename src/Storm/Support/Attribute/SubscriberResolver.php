<?php

declare(strict_types=1);

namespace Storm\Support\Attribute;

use Illuminate\Support\Collection;
use ReflectionException;
use RuntimeException;
use Storm\Attribute\Reader;
use Storm\Attribute\ReflectionUtil;
use Storm\Contract\Tracker\Listener;
use Storm\Reporter\Attribute\AsSubscriber;
use Storm\Tracker\GenericListener;

use function is_object;
use function is_string;

final class SubscriberResolver extends Reader
{
    /**
     * @return Collection<Listener>
     *
     * @throws ReflectionException
     */
    public function resolve(string|object $class): Collection
    {
        $className = is_string($class) ? $class : $class::class;

        $reflectionClass = ReflectionUtil::createReflectionClass($class);

        return $this->readAttribute($reflectionClass, AsSubscriber::class)
            ->whenEmpty(function () use ($className) {
                throw new RuntimeException("Missing #AsSubscriber attribute for class $className");
            })->map(function (AsSubscriber $attribute) use ($reflectionClass, $class) {
                $method = $attribute->method ?? '__invoke';

                /**
                 * only allow methods without parameters
                 * use constructor injection instead with @Reference bindings
                 * as built-in is not supported
                 */
                $parameters = $reflectionClass->getMethod($method)->getParameters();
                if ($parameters !== []) {
                    throw new RuntimeException("Method $method for class {$reflectionClass->getName()} must not have parameters");
                }

                // in case we already init attribute class
                $instance = is_object($class) ? $class : $this->createInstance($reflectionClass);

                return new GenericListener($attribute->eventName, $instance->$method()(...), $attribute->priority);
            });
    }
}
