<?php

declare(strict_types=1);

namespace Storm\Reporter;

use Storm\Contract\Reporter\Router;
use Storm\Reporter\Exception\MessageNotFound;

final readonly class Routing
{
    public function __construct(private Router $router)
    {
    }

    /**
     * @throws MessageNotFound
     */
    public function route(string $messageName): array
    {
        $handlers = $this->router->get($messageName);

        if ($handlers === null) {
            throw MessageNotFound::withMessageName($messageName);
        }

        return $handlers;
    }
}
