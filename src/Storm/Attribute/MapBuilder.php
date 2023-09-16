<?php

declare(strict_types=1);

namespace Storm\Attribute;

use Composer\Autoload\ClassLoader;
use Illuminate\Support\Collection;
use ReflectionClass;

use function class_exists;
use function str_starts_with;

final readonly class MapBuilder
{
    private ClassLoader $classLoader;

    public function __construct(
        private AttributeFile $files,
        private AttributeFactory $factory,
        string $autoload = __DIR__.'/../../../vendor/autoload.php'
    ) {
        $this->classLoader = require $autoload;
    }

    // todo env
    public function inMemory(): Collection
    {
        return $this->buildMap();
    }

    public function build(): Collection
    {
        if (! $this->files->exists()) {
            $map = $this->buildMap();

            $this->files->compile($map);

            return $map;
        }

        return $this->files->get();
    }

    public function update(): void
    {
        if ($this->files->exists()) {
            $this->files->delete();
        }

        $this->build();
    }

    private function buildMap(): Collection
    {
        $map = $this->getAutoloadClasses();

        $classes = $this->filterClasses($map);

        return $this->factory->make($classes);
    }

    /**
     * @return Collection{class-string, string}
     */
    private function getAutoloadClasses(): Collection
    {
        return collect($this->classLoader->getClassMap());
    }

    /**
     * @return Collection{class-string, ReflectionClass}
     */
    private function filterClasses(Collection $classes): Collection
    {
        return $classes
            ->filter(function (string $path, string $class): bool {
                return str_starts_with($class, 'Storm\\') && class_exists($class);
            })->map(function (string $path, string $class): ReflectionClass {
                return new ReflectionClass($class);
            });
    }
}
