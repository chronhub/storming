<?php

declare(strict_types=1);

namespace Storm\Support\Attribute;

use ReflectionException;
use RuntimeException;
use Storm\Attribute\Reader;
use Storm\Attribute\ReflectionUtil;
use Storm\Contract\Reporter\MessageFilter;
use Storm\Contract\Reporter\Reporter;
use Storm\Reporter\Attribute\AsReporter;

use function is_string;

class ReporterResolver extends Reader
{
    /**
     * @param  class-string                                     $className
     * @return array{Reporter, non-empty-string, MessageFilter}
     *
     * @throws ReflectionException
     */
    public function resolve(string $className): array
    {
        $reflectionClass = ReflectionUtil::createReflectionClass($className);

        return $this->readAttribute($reflectionClass, AsReporter::class)
            ->whenEmpty(function () use ($className) {
                throw new RuntimeException("Missing #AsReporter attribute for class $className");
            })
            ->map(function (AsReporter $attribute) use ($reflectionClass) {
                $instance = $this->createInstance($reflectionClass);

                $filter = $attribute->filter;

                if (is_string($filter)) {
                    $filter = $this->container[$filter];
                }

                $instanceId = $attribute->name ?? $reflectionClass->getName();

                return [$instance, $instanceId, $filter];
            })->first();
    }
}
