<?php

declare(strict_types=1);

namespace Storm\Attribute;

use Illuminate\Support\Collection;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use Storm\Reporter\Attribute\AsMessageHandler;
use Storm\Reporter\Attribute\AsReporter;
use Storm\Reporter\Attribute\AsSubscriber;

class AttributeFactory
{
    public function make(Collection $classes): Collection
    {
        return collect([
            AsReporter::class => $this->findReporterAttributes($classes),
            AsSubscriber::class => $this->findSubscriberAttributes($classes),
            AsMessageHandler::class => $this->findHandlerAttributes($classes),
        ]);
    }

    protected function findReporterAttributes(Collection $classes): Collection
    {
        return $classes->map(function (ReflectionClass $reflectionClass) {
            $attributes = $this->findAttributesInClass($reflectionClass, AsReporter::class);

            if ($attributes === null) {
                return null;
            }

            /** @var AsReporter $asReporter */
            $asReporter = $attributes[0]->newInstance();

            return $asReporter->name ?? $reflectionClass->getName();
        })->filter();
    }

    /**
     * CheckMe by now its not worth it to cache it
     *
     * @return Collection<class-string>
     */
    protected function findSubscriberAttributes(Collection $classes): Collection
    {
        return $classes->map(function (ReflectionClass $reflectionClass) {
            return $this->findAttributesInClass($reflectionClass, AsSubscriber::class);
        })->filter()->keys();
    }

    /**
     * @return Collection<array<class-string, non-empty-string>>
     */
    protected function findHandlerAttributes(Collection $classes): Collection
    {
        return $classes->map(function (ReflectionClass $reflectionClass) {
            $attributes = $this->findAttributesInClass($reflectionClass, AsMessageHandler::class);

            if ($attributes !== null) {
                return $this->normalizeMessageHandler($reflectionClass);
            }

            $reflectionMethods = ReflectionUtil::getPublicMethodsByAttribute($reflectionClass, AsMessageHandler::class);

            if ($reflectionMethods === []) {
                return null;
            }

            $messageHandlers = [];

            foreach ($reflectionMethods as $reflectionMethod) {
                $messageHandlers[] = $this->normalizeMessageHandler($reflectionMethod);
            }

            return $messageHandlers;
        })->filter();
    }

    /**
     * @return array<class-string, non-empty-string> [type name parameter aka message => method name]
     *
     * @throws RuntimeException when no invokable method is found in class
     */
    protected function normalizeMessageHandler(ReflectionClass|ReflectionMethod $reflector): array
    {
        $attributes = $reflector->getAttributes(AsMessageHandler::class, ReflectionAttribute::IS_INSTANCEOF);

        if ($attributes !== []) {
            $attributes[0]->newInstance();
        }

        if ($reflector instanceof ReflectionClass) {
            $reflector = ReflectionUtil::requirePublicInvokableMethod($reflector->getMethods(ReflectionMethod::IS_PUBLIC));
        }

        return [ReflectionUtil::requireFirstParameterTypeName($reflector) => $reflector->getName()];
    }

    /**
     * @param  class-string                    $attribute
     * @return array<ReflectionAttribute>|null
     */
    protected function findAttributesInClass(ReflectionClass $reflectionClass, string $attribute): ?array
    {
        $attributes = $reflectionClass->getAttributes($attribute, ReflectionAttribute::IS_INSTANCEOF);

        return $attributes === [] ? null : $attributes;
    }
}
