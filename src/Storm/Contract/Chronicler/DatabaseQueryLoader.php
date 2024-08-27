<?php

declare(strict_types=1);

namespace Storm\Contract\Chronicler;

use Generator;
use Illuminate\Database\Query\Builder;
use Storm\Chronicler\Exceptions\NoStreamEventReturn;
use Storm\Chronicler\Exceptions\StreamNotFound;
use Storm\Contract\Message\DomainEvent;
use Storm\Stream\StreamName;

interface DatabaseQueryLoader extends StreamEventLoader
{
    /**
     * Load events from a stream.
     *
     * @return Generator<DomainEvent|array>
     *
     * @throws NoStreamEventReturn when no stream event return
     * @throws StreamNotFound      when stream not found
     */
    public function load(Builder $builder, StreamName $streamName): Generator;
}
