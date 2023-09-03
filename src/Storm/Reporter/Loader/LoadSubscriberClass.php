<?php

declare(strict_types=1);

namespace Storm\Reporter\Loader;

use ReflectionClass;
use ReflectionException;
use ReflectionParameter;
use RuntimeException;
use Storm\Contract\Tracker\Listener;
use Storm\Reporter\Attribute\AsSubscriber;
use Storm\Tracker\GenericListener;

use function is_string;

class LoadSubscriberClass
{
    /**
     * @return array<Listener>
     *
     * @throws RuntimeException    when attribute is missing
     * @throws ReflectionException
     */
    public static function from(string|object $class): array
    {
        // todo with both message and stream
        $reflectionClass = new ReflectionClass($class);
        $attributes = $reflectionClass->getAttributes(AsSubscriber::class);

        if ($attributes === []) {
            $className = is_string($class) ? $class : $class::class;

            throw new RuntimeException("Missing attribute #AsSubscriber for class $className");
        }

        $listeners = [];

        foreach ($attributes as $attribute) {
            $method = $attribute->getArguments()['method'] ?? '__invoke';
            $parameters = $reflectionClass->getMethod($method)->getParameters();

            $listeners[] = new GenericListener(
                $attribute->getArguments()['eventName'],
                self::getStory($class, $method, $parameters),
                $attribute->getArguments()['priority'],
            );
        }

        return $listeners;
    }

    /**
     * @param array<ReflectionParameter> $parameters>
     */
    private static function getStory(string|object $subscriber, string $method, array $parameters): callable
    {
        if (is_string($subscriber)) {
            $subscriber = app($subscriber);
        }

        // fixMe parameters bindings?
        // fixMe bring container

        return app()->call([$subscriber, $method], $parameters);
    }
}
