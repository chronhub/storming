<?php

declare(strict_types=1);

namespace Storm\Projector\Connector;

use Illuminate\Contracts\Events\Dispatcher;
use Storm\Contract\Chronicler\Chronicler;
use Storm\Contract\Chronicler\EventStreamProvider;
use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Projector\ProjectionProvider;
use Storm\Contract\Serializer\SymfonySerializer;
use Storm\Projector\Options\Option;

interface ConnectionManager
{
    public function eventStore(): Chronicler;

    public function eventStreamProvider(): EventStreamProvider;

    public function projectionProvider(): ProjectionProvider;

    public function serializer(): SymfonySerializer;

    public function clock(): SystemClock;

    public function dispatcher(): ?Dispatcher;

    public function toProjectionOption(array $options = []): Option;

    public function connectionName(): string;
}
