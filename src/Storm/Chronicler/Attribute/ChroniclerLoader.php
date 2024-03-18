<?php

declare(strict_types=1);

namespace Storm\Chronicler\Attribute;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use Storm\Annotation\AnnotationLoader;
use Storm\Message\Attribute\MessageAttribute;

class ChroniclerLoader extends AnnotationLoader
{
    public const string ATTRIBUTE_NAME = AsChronicler::class;

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
        $classes = config('annotation.chroniclers', []);

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
            ->each(function (AsChronicler $attribute) use ($reflectionClass): void {
                $this->attributes->push(
                    new ChroniclerAttribute(
                        $reflectionClass->getName(),
                        $attribute->connection,
                        $attribute->tableName,
                        $attribute->persistence,
                        $attribute->eventable,
                        $attribute->transactional,
                        $attribute->evenStreamProvider,
                        $attribute->streamEventLoader,
                        $attribute->abstract,
                        Arr::wrap($attribute->subscribers),
                        $attribute->decoratorFactory,
                    ),
                );
            });
    }
}
