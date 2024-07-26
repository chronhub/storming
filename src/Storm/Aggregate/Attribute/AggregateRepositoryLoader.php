<?php

declare(strict_types=1);

namespace Storm\Aggregate\Attribute;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use Storm\Annotation\AnnotationLoader;
use Storm\Annotation\Reference\ReferenceBuilder;
use Storm\Reporter\Attribute\Subscriber\SubscriberAttribute;

use function count;

class AggregateRepositoryLoader extends AnnotationLoader
{
    public const string ATTRIBUTE_NAME = AsAggregateRepository::class;

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
        $classes = config('annotation.aggregate_repositories');

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
            ->each(function (AsAggregateRepository $attribute) use ($reflectionClass): void {
                $abstract = $this->determineAbstract($attribute->abstract, $reflectionClass->getInterfaceNames());

                $this->attributes->push(
                    new AggregateRepositoryAttribute(
                        $reflectionClass->getName(),
                        $abstract,
                        $attribute->chronicler,
                        $attribute->streamName,
                        Arr::wrap($attribute->aggregateRoot),
                        $attribute->messageDecorator,
                        $attribute->factory,
                        $this->referenceBuilder->fromConstructor($reflectionClass)
                    ),
                );
            });
    }

    protected function determineAbstract(?string $abstract, array $interfaces): string
    {
        if ($abstract !== null) {
            return $abstract;
        }

        if (count($interfaces) === 1) {
            return $interfaces[0];
        }

        throw new RuntimeException('Could not determine abstract for aggregate repository, no interface or too many found, please specify one manually.');
    }
}
