<?php

declare(strict_types=1);

namespace Storm\Reporter;

use Illuminate\Support\Arr;
use Storm\Attribute\Loader;

class ReporterMap
{
    /**
     * @var array[]
     */
    protected array $map;

    public function __construct(Loader $loader)
    {
        $this->map = $loader->getReporters();
    }

    /**
     * @return array{class-string, non-empty-string, non-empty-string, non-empty-string}|null
     */
    public function find(string $name): ?array
    {
        foreach ($this->map as $map) {
            if ($map['class'] === $name || $map['alias'] === $name) {
                return [$map['class'], $map['alias'], $map['tracker'], $map['filter']];
            }
        }

        return null;
    }

    public function list(): array
    {
        return Arr::mapWithKeys($this->map, fn (array $map): array => [$map['class'] => $map['alias']]);
    }
}
