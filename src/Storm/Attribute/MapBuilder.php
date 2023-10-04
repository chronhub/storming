<?php

declare(strict_types=1);

namespace Storm\Attribute;

use Illuminate\Support\Collection;

use function iterator_to_array;

final readonly class MapBuilder
{
    public function __construct(
        private AttributeFile $files,
        private AttributeFactory $factory,
        private MapFactory $mapFactory,
    ) {
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
        $map = $this->mapFactory->fromDirectories(
            [
                __DIR__.'/../../../src',
                __DIR__.'/../../../tests/Stubs',
            ]
        );

        return $this->factory->make(collect(iterator_to_array($map)));
    }
}
