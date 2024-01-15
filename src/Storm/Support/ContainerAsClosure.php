<?php

declare(strict_types=1);

namespace Storm\Support;

use Closure;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Foundation\Application;

final readonly class ContainerAsClosure
{
    /**
     * @deprecated
     */
    public Application $container;

    public function __construct(Closure $container)
    {
        $this->container = $container();
    }

    public function getApplication(): Application
    {
        return $this->container;
    }

    public function getContainer(): Container
    {
        return $this->container;
    }
}
