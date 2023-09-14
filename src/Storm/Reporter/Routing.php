<?php

declare(strict_types=1);

namespace Storm\Reporter;

use Storm\Attribute\Loader;
use Storm\Attribute\ResolverFactory;
use Storm\Reporter\Attribute\AsMessageHandler;
use Storm\Support\Attribute\MessageHandlerInstance;

class Routing
{
    public function __construct(
        protected Loader $loader,
        protected ResolverFactory $factory
    ) {
    }

    /**
     * @return array<empty|callable>
     */
    public function route(string $messageName): array
    {
        $messageHandlers = $this->loader->getMessageHandlers($messageName);

        $handlers = [];

        foreach ($messageHandlers as $messageHandler) {
            /** @var MessageHandlerInstance $handler */
            $handler = $this->factory->make(AsMessageHandler::class)->resolve($messageHandler);

            $handlers[] = $handler->call();
        }

        return $handlers;
    }

    public function hasMessageName(string $messageName): bool
    {
        return $this->loader->hasMessageName($messageName);
    }
}
