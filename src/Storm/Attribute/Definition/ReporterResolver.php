<?php

declare(strict_types=1);

namespace Storm\Attribute\Definition;

use Illuminate\Support\Collection;
use ReflectionAttribute;
use ReflectionClass;
use Storm\Reporter\Attribute\AsReporter;

final class ReporterResolver extends TypeResolver
{
    public function find(Collection $classes): array
    {
        return $classes->map(function (ReflectionClass $reflectionClass) {
            $attributes = $this->findAttributesInClass($reflectionClass, AsReporter::class);

            if ($attributes === null) {
                return null;
            }

            $asReporter = $this->getFirstAttributeInstance($attributes);

            return new ReporterDefinition(
                $reflectionClass->getName(),
                $asReporter->name ?? $reflectionClass->getName(),
                $asReporter->getStringFilter(),
                $asReporter->getStringTracker(),
            );
        })->filter()->jsonSerialize();
    }

    /**
     * @param array<ReflectionAttribute> $attributes
     */
    private function getFirstAttributeInstance(array $attributes): AsReporter
    {
        return $attributes[0]->newInstance();
    }
}
