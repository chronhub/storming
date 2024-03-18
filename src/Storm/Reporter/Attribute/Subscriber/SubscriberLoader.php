<?php

declare(strict_types=1);

namespace Storm\Reporter\Attribute\Subscriber;

use Illuminate\Support\Collection;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use Storm\Annotation\AnnotationLoader;
use Storm\Annotation\Reference\ReferenceBuilder;

class SubscriberLoader extends AnnotationLoader
{
    public const string ATTRIBUTE_NAME = AsReporterSubscriber::class;

    /**
     * @var Collection<SubscriberAttribute>
     */
    protected Collection $attributes;

    public function __construct(protected ReferenceBuilder $referenceBuilder)
    {
        $this->attributes = new Collection();
    }

    public function getAttributes(): Collection
    {
        $classes = config('annotation.reporter_subscribers', []);

        $this->loadAttributes(collect($classes));

        return $this->attributes;
    }

    protected function loadAttributes(Collection $classes): void
    {
        $classes
            ->map(fn (string $class): ReflectionClass => new ReflectionClass($class))
            ->each(function (ReflectionClass $reflectionClass): void {
                $this->findAttributesInClass($reflectionClass, self::ATTRIBUTE_NAME);

                $this->findAttributesInMethods($reflectionClass, self::ATTRIBUTE_NAME);
            });
    }

    protected function processAttributes(ReflectionClass $reflectionClass, ?ReflectionMethod $reflectionMethod, Collection $attributes): void
    {
        $attributes
            ->map(fn (ReflectionAttribute $attribute): object => $attribute->newInstance())
            ->each(function (AsReporterSubscriber $attribute) use ($reflectionClass, $reflectionMethod): void {
                $this->attributes->push(
                    new SubscriberAttribute(
                        $reflectionClass->getName(),
                        $attribute->event,
                        $attribute->supports,
                        $this->determineMethodName($attribute->method, $reflectionMethod),
                        $attribute->priority,
                        $attribute->alias,
                        $attribute->autowire,
                        $this->referenceBuilder->fromConstructor($reflectionClass)
                    ),
                );
            });
    }
}
