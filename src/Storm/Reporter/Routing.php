<?php

declare(strict_types=1);

namespace Storm\Reporter;

use Storm\Attribute\Loader;
use Storm\Attribute\ResolverFactory;

class Routing
{
    public function __construct(
        protected Loader $loader,
        protected ResolverFactory $factory
    ) {
    }

    /**
     * @return array<empty|callable>
     *
     * @throws MessageNotFound
     */
    public function route(string $messageName): array
    {
        if (! $this->hasMessageName($messageName)) {
            throw MessageNotFound::withMessageName($messageName);
        }

        $messageHandlers = $this->loader->getMessageHandlers($messageName);

        $handlers = [];

        foreach ($messageHandlers as $messageHandler => $handlerMethodName) {
            $handler = $this->factory->toMessageHandler($messageHandler);

            $handlers[] = $handler->call();
        }

        return $handlers;
    }

    public function hasMessageName(string $messageName): bool
    {
        return $this->loader->hasMessageName($messageName);
    }
}
