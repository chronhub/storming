<?php

declare(strict_types=1);

namespace Storm\Contract\Reporter;

interface ReporterManager
{
    public function create(string $name): Reporter;

    public function provides(): array;
}
