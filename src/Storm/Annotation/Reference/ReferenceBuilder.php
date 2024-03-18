<?php

declare(strict_types=1);

namespace Storm\Annotation\Reference;

use Illuminate\Contracts\Container\Container;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;

use function is_string;

/**
 * @template Ref of array<string, string>
 */
class ReferenceBuilder
{
    public function __construct(protected Container $container)
    {
    }

    /**
     * Find references in reflection class constructor
     *
     * @return array<array{'__construct': string, Ref}>|array
     *
     * @throws ReflectionException
     */
    public function fromConstructor(string|ReflectionClass $reflectionClass): array
    {
        if (is_string($reflectionClass)) {
            $reflectionClass = new ReflectionClass($reflectionClass);
        }

        $constructor = $reflectionClass->getConstructor();

        if ($constructor === null) {
            return [];
        }

        $references = [];

        foreach ($constructor->getParameters() as $parameter) {
            $attributes = $parameter->getAttributes(Reference::class, ReflectionAttribute::IS_INSTANCEOF);

            foreach ($attributes as $attribute) {
                $instance = $attribute->newInstance();

                $references[] = [$parameter->getName(), $instance->name];
            }
        }

        return [$constructor->getName() => $references];
    }
}
