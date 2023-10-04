<?php

declare(strict_types=1);

namespace Storm\Contract\Reporter;

interface Router
{
    /**
     * @param  class-string               $name message name
     * @return array<empty|callable>|null
     */
    public function get(string $name): ?array;
}
