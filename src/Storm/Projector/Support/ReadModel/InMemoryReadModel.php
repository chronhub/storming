<?php

declare(strict_types=1);

namespace Storm\Projector\Support\ReadModel;

use Storm\Contract\Projector\ReadModel;

final class InMemoryReadModel implements ReadModel
{
    use InMemoryContainerStack;

    public function initialize(): void
    {
        $this->container = [];
    }

    public function isInitialized(): bool
    {
        return true;
    }

    public function reset(): void
    {
        $this->container = [];
    }

    public function down(): void
    {
        $this->reset();
    }

    public function getContainer(): array
    {
        return $this->container;
    }
}
