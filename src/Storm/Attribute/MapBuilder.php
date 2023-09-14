<?php

declare(strict_types=1);

namespace Storm\Attribute;

use Illuminate\Support\Collection;
use ReflectionClass;

use function class_exists;
use function str_starts_with;

final class MapBuilder
{
    protected Collection $map;

    public function __construct(
        private readonly AttributeFile $files,
        private readonly AttributeFactory $factory,
    ) {
    }

    public function inMemory(): void
    {
        $this->map = $this->buildMap();
    }

    public function compile(): void
    {
        if (! $this->files->exists()) {
            $this->map = $this->buildMap();

            $this->files->compile($this->map);
        } else {
            $this->map = $this->files->get();
        }
    }

    public function update(): void
    {
        if ($this->files->exists()) {
            $this->files->delete();
        }

        $this->compile();
    }

    public function getMap(): Collection
    {
        return $this->map;
    }

    private function buildMap(): Collection
    {
        $map = $this->getAutoloadClasses();

        $classes = $this->filterStormClasses($map);

        return $this->factory->make($classes);
    }

    /**
     * @return Collection{class-string, string}
     */
    private function getAutoloadClasses(): Collection
    {
        $classLoader = require __DIR__.'/../../../vendor/autoload.php';

        return collect($classLoader->getClassMap());
    }

    /**
     * @return Collection{class-string, ReflectionClass}
     */
    private function filterStormClasses(Collection $classes): Collection
    {
        return $classes
            ->filter(function (string $path, string $class): bool {
                return str_starts_with($class, 'Storm\\') && class_exists($class);
            })->map(function (string $path, string $class): ReflectionClass {
                return new ReflectionClass($class);
            });
    }
}
