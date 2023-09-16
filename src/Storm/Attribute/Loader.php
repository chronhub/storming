<?php

declare(strict_types=1);

namespace Storm\Attribute;

use Illuminate\Support\Collection;
use Storm\Reporter\Attribute\AsMessageHandler;
use Storm\Reporter\Attribute\AsReporter;

use function class_exists;

class Loader
{
    private Collection $map;

    public function __construct(protected MapBuilder $mapBuilder)
    {
        $this->map = $this->mapBuilder->inMemory();
    }

    /**
     * Find reporter class name by alias or class name.
     *
     * @param  class-string|non-empty-string                           $name
     * @return array{class-string, non-empty-string|class-string}|null
     */
    public function getReporter(string $name): ?array
    {
        $reporterMap = $this->map[AsReporter::class];

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
        return $this->map[AsMessageHandler::class]->get($messageName, []);
    }

    /**
     * Check if message name exists.
     *
     * @param class-string $messageName
     */
    public function hasMessageName(string $messageName): bool
    {
        return $this->map[AsMessageHandler::class]->has($messageName);
    }

    public function getMap(): Collection
    {
        return $this->map;
    }
}
