<?php

declare(strict_types=1);

namespace Storm\Attribute;

use Illuminate\Support\Collection;
use Storm\Reporter\Attribute\AsMessageHandler;
use Storm\Reporter\Attribute\AsReporter;
use Storm\Reporter\Attribute\AsSubscriber;

class Loader
{
    private Collection $map;

    public function __construct(protected MapBuilder $mapBuilder)
    {
        $this->map = $this->mapBuilder->inMemory();
    }

    /**
     * @return array<class-string, array{class: class-string, alias: non-empty-string, filter: non-empty-string, tracker: non-empty-string}>
     */
    public function getReporters(): array
    {
        return $this->map[AsReporter::class]; //fixme remove key from array return
    }

    public function getSubscribers(): array
    {
        return $this->map[AsSubscriber::class];
    }

    public function getHandlers(): array
    {
        return $this->map[AsMessageHandler::class];
    }

    public function getMap(): Collection
    {
        return $this->map;
    }
}
