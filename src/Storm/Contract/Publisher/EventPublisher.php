<?php

declare(strict_types=1);

namespace Storm\Contract\Publisher;

interface EventPublisher extends MarshallEventPublisher
{
    public function record(iterable $streamEvents): void;

    public function pull(): iterable;

    public function flush(): void;
}
