<?php

declare(strict_types=1);

namespace Storm\Projector\Connector;

use Illuminate\Contracts\Events\Dispatcher;
use Storm\Contract\Chronicler\Chronicler;
use Storm\Contract\Chronicler\EventStreamProvider;
use Storm\Contract\Chronicler\QueryFilter;
use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Projector\ProjectionProvider;
use Storm\Contract\Serializer\SymfonySerializer;
use Storm\Projector\Options\Option;
use Storm\Projector\Repository\EventRepository;

interface ConnectionManager
{
    public function eventStore(): Chronicler;

    public function eventStreamProvider(): EventStreamProvider;

    public function projectionProvider(): ProjectionProvider;

    public function queryFilter(): QueryFilter;

    public function serializer(): SymfonySerializer;

    public function clock(): SystemClock;

    /**
     * Get the laravel event dispatcher if available.
     * It means to decorate the repository projector to dispatch events.
     *
     * @see EventRepository
     */
    public function dispatcher(): ?Dispatcher;

    /**
     * Conditionally merge options with the default class option.
     */
    public function toOption(array $options = []): Option;
}
