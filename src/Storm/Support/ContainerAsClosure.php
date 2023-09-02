<?php

declare(strict_types=1);

namespace Storm\Support;

use Closure;
use Illuminate\Contracts\Container\Container;

final readonly class ContainerAsClosure
{
    public Container $container;

    public function __construct(Closure $container)
    {
        $this->container = $container();
    }
}
