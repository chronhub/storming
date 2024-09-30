<?php

declare(strict_types=1);

namespace Storm\Contract\Aggregate;

use Closure;
use Illuminate\Contracts\Foundation\Application;

interface AggregateManager
{
    /**
     * Create a new aggregate repository instance.
     */
    public function create(string $name, ?string $factory = null): AggregateRepository;

    /**
     * Add a new aggregate repository factory.
     *
     * @param (Closure(Application): AggregateFactory) $factory
     */
    public function addFactory(string $name, Closure $factory): void;
}
