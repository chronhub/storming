<?php

declare(strict_types=1);

namespace Storm\Contract\Reporter;

interface ReporterManager
{
    /**
     * @param non-empty-string|class-string $name
     */
    public function create(string $name): Reporter;

    /**
     * @param non-empty-string $name
     * @param class-string     $className
     */
    public function addAlias(string $name, string $className): void;
}
