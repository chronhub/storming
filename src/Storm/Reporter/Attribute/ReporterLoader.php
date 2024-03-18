<?php

declare(strict_types=1);

namespace Storm\Reporter\Attribute;

use Illuminate\Support\Collection;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use Storm\Annotation\AnnotationLoader;
use Storm\Message\Attribute\MessageAttribute;

class ReporterLoader extends AnnotationLoader
{
    public const string ATTRIBUTE_NAME = AsReporter::class;

    /**
     * @var Collection<MessageAttribute>
     */
    protected Collection $attributes;

    public function __construct()
    {
        $this->attributes = new Collection();
    }

    public function getAttributes(): Collection
    {
        $classes = config('annotation.reporters', []);

        $this->loadAttributes(collect($classes));

        return $this->attributes;
    }

    protected function loadAttributes(Collection $classes): void
    {
        $classes
            ->map(fn (string $class): ReflectionClass => new ReflectionClass($class))
            ->each(function (ReflectionClass $reflectionClass): void {
                $this->findAttributesInClass($reflectionClass, self::ATTRIBUTE_NAME);
            });
    }

    protected function processAttributes(ReflectionClass $reflectionClass, ?ReflectionMethod $reflectionMethod, Collection $attributes): void
    {
        $attributes
            ->map(fn (ReflectionAttribute $attribute): object => $attribute->newInstance())
            ->each(function (AsReporter $attribute) use ($reflectionClass): void {
                $this->attributes->push(
                    new ReporterAttribute(
                        $attribute->id,
                        $reflectionClass->getName(),
                        $attribute->type->value,
                        $attribute->mode->value,
                        $attribute->listeners,
                        $attribute->defaultQueue,
                        $attribute->tracker,
                    ),
                );
            });
    }
}
