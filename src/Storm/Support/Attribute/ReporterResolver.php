<?php

declare(strict_types=1);

namespace Storm\Support\Attribute;

use Illuminate\Support\Collection;
use ReflectionClass;
use Storm\Attribute\TypeResolver;
use Storm\Contract\Tracker\MessageTracker;
use Storm\Reporter\Attribute\AsReporter;

use function is_string;

final class ReporterResolver extends TypeResolver
{
    public const ATTRIBUTE_NAME = AsReporter::class;

    public function process(Collection $attributes, ReflectionClass $reflectionClass, string|object $original): ReporterInstance
    {
        return $attributes
            ->whenEmpty(function () use ($reflectionClass) {
                $this->raiseMissingAttributeException($reflectionClass);
            })
            ->map(function (AsReporter $attribute) use ($reflectionClass) {
                $instance = $this->createInstance(
                    $reflectionClass,
                    [$this->resolveTracker($attribute->tracker)]
                );

                return new ReporterInstance($instance, $attribute->name ?? $instance::class, $attribute->filter);
            })->first();
    }

    public function getSupportedAttribute(): string
    {
        return self::ATTRIBUTE_NAME;
    }

    private function resolveTracker(string|MessageTracker $tracker): object
    {
        if (is_string($tracker)) {
            return $this->container[$tracker];
        }

        return $tracker;
    }
}
