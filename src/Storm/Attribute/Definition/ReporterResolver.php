<?php

declare(strict_types=1);

namespace Storm\Attribute\Definition;

use Illuminate\Support\Collection;
use ReflectionClass;
use Storm\Reporter\Attribute\AsReporter;

use function is_string;

final class ReporterResolver extends AttributeResolver
{
    public function find(Collection $classes): Collection
    {
        return $classes->map(function (ReflectionClass $reflectionClass) {
            $attributes = $this->findAttributesInClass($reflectionClass, AsReporter::class);

            if ($attributes === null) {
                return null;
            }

            /** @var AsReporter $asReporter */
            $asReporter = $attributes[0]->newInstance();

            return new ReporterDefinition(
                $reflectionClass->getName(),
                $asReporter->name ?? $reflectionClass->getName(),
                is_string($asReporter->filter) ? $asReporter->filter : $asReporter->filter::class,
                is_string($asReporter->tracker) ? $asReporter->tracker : $asReporter->tracker::class,
            );
        })->filter();
    }
}
