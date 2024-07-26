<?php

declare(strict_types=1);

namespace Storm\Contract\Reporter;

interface Routable
{
    /**
     * Route message to his handler(s).
     *
     * Return null, assume that message is not found.
     *
     * @return array<callable>|array|null
     */
    public function route(string $reporterId, string $message): ?array;
}
