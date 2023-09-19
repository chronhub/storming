<?php

declare(strict_types=1);

namespace Storm\Attribute;

use Illuminate\Support\Collection;
use Storm\Reporter\Attribute\AsMessageHandler;
use Storm\Reporter\Attribute\AsReporter;
use Storm\Reporter\Attribute\AsSubscriber;

class MapLoader
{
    protected Collection $map;

    public function __construct(protected MapBuilder $builder)
    {
        $this->map = $this->builder->inMemory();
    }

    public function getReporters(): array
    {
        return $this->map[AsReporter::class];
    }

    public function getMessages(): array
    {
        return $this->map[AsMessageHandler::class];
    }

    public function getSubscribers(): array
    {
        return $this->map[AsSubscriber::class];
    }
}
