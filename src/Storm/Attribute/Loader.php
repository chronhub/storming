<?php

declare(strict_types=1);

namespace Storm\Attribute;

use Illuminate\Support\Collection;
use Storm\Reporter\Attribute\AsMessageHandler;
use Storm\Reporter\Attribute\AsReporter;

use function array_key_exists;
use function class_exists;

class Loader
{
    public function __construct(protected MapBuilder $mapBuilder)
    {
        // $this->mapBuilder->compile();
        $this->mapBuilder->inMemory();
    }

    /**
     * Find reporter class name by alias or class name.
     *
     * @param  class-string|non-empty-string                           $name
     * @return array{class-string, non-empty-string|class-string}|null
     */
    public function getReporter(string $name): ?array
    {
        $reporterMap = $this->mapBuilder->getMap()[AsReporter::class];

        if (class_exists($name)) {
            $alias = $reporterMap[$name] ?? null;

            return $alias !== null ? [$name, $alias] : null;
        }

        foreach ($reporterMap as $reporterClass => $reporterAlias) {
            if ($reporterAlias === $name) {
                return [$reporterClass, $name];
            }
        }

        return null;
    }

    /**
     * Find message handler(s) class name by message name.
     *
     * @param  class-string              $messageName
     * @return array<class-string|empty> message handler class name
     */
    public function getMessageHandlers(string $messageName): array
    {
        $messageHandlers = $this->mapBuilder->getMap()[AsMessageHandler::class];

        $found = [];

        foreach ($messageHandlers as $messageHandlerClass => $handlers) {
            foreach ($handlers as $messageClass => $method) {
                if ($messageClass === $messageName) {
                    $found[] = $messageHandlerClass;
                }
            }
        }

        return $found;
    }

    /**
     * Check if message name exists.
     *
     * @param class-string $messageName
     */
    public function hasMessageName(string $messageName): bool
    {
        $messageHandlers = $this->mapBuilder->getMap()[AsMessageHandler::class];

        $found = false;
        foreach ($messageHandlers as $handlers) {
            if (array_key_exists($messageName, $handlers)) {
                $found = true;
            }
        }

        return $found;
    }

    public function getMap(): Collection
    {
        return $this->mapBuilder->getMap();
    }
}
