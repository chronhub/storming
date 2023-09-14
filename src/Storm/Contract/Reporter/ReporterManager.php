<?php

declare(strict_types=1);

namespace Storm\Contract\Reporter;

interface ReporterManager
{
    /**
     * @param non-empty-string|class-string $name
     */
    public function create(string $name): Reporter;
}
