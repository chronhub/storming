<?php

declare(strict_types=1);

namespace Storm\Reporter;

use Illuminate\Contracts\Container\Container;
use ReflectionAttribute;
use ReflectionClass;
use RuntimeException;
use Storm\Contract\Reporter\Reporter;
use Storm\Reporter\Attribute\AsReporter;
use Storm\Reporter\Subscriber\FilterMessage;
use Storm\Reporter\Subscriber\NameReporter;

use function count;
use function is_a;
use function is_string;

class ReporterFactory
{
    public function __construct(protected readonly Container $container)
    {
    }

    public function create(string $name): Reporter
    {
        $reflectionClass = $this->reflectClass($name);
        $reflectionAttribute = $this->reflectAttribute($reflectionClass);

        $instance = $reflectionAttribute->newInstance();

        [$nameReporter, $filter, $tracker] = $this->configure($instance, $name);

        /** @var Reporter $reporter */
        $reporter = $reflectionClass->newInstanceArgs([$tracker]);

        $reporter->subscribe($nameReporter, $filter);

        return $reporter;
    }

    private function configure(AsReporter $attribute, string $reporterClass): array
    {
        $name = $attribute->name ?? $reporterClass;
        $filter = $attribute->filter;
        $tracker = $attribute->tracker;

        if (is_string($filter)) {
            $filter = $this->container[$filter];
        }

        if (is_string($tracker)) {
            $tracker = $this->container[$tracker];
        }

        return [new NameReporter($name), new FilterMessage($filter), $tracker];
    }

    private function reflectClass(string $name): ReflectionClass
    {
        if (! is_a($name, Reporter::class, true)) {
            throw new RuntimeException("Class $name must implement ".Reporter::class);
        }

        return new ReflectionClass($name);
    }

    private function reflectAttribute(ReflectionClass $class): ReflectionAttribute
    {
        $attributes = $class->getAttributes(AsReporter::class);

        if (count($attributes) !== 1) {
            throw new RuntimeException("Missing attribute #AsReporter for class {$class->getName()}");
        }

        return $attributes[0];
    }
}
