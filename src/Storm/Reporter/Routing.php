<?php

declare(strict_types=1);

namespace Storm\Reporter;

use Illuminate\Contracts\Container\Container;
use Storm\Support\ContainerAsClosure;
use Storm\Support\MessageAliasBinding;

class Routing
{
    private Container $container;

    public function __construct(ContainerAsClosure $container)
    {
        $this->container = $container->container;
    }

    /**
     * @return array<empty|callable>
     *
     * @throws MessageNotFound
     */
    public function route(string $messageName): array
    {
        $alias = MessageAliasBinding::fromMessageName($messageName);

        if (! $this->container->has($alias)) {
            throw MessageNotFound::withMessageName($messageName);
        }

        return $this->container[$alias];
    }
}
