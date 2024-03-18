<?php

declare(strict_types=1);

namespace Storm\Contract\Reporter;

interface Routable
{
    /**
     * Route message to his handler(s).
     *
     * @return array<callable>|null
     */
    public function route(string $reporterId, string $message): ?array;
}
