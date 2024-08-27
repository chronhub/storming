<?php

declare(strict_types=1);

namespace Storm\Contract\Chronicler;

use Storm\Chronicler\Exceptions\ConcurrencyException;
use Storm\Chronicler\Exceptions\QueryFailure;
use Storm\Chronicler\Exceptions\StreamNotFound;
use Storm\Stream\Stream;
use Storm\Stream\StreamName;
use Throwable;

interface Chronicler extends ReadOnlyChronicler
{
    /**
     * Append a new event to a stream.
     *
     * @throws StreamNotFound       when stream not found
     * @throws ConcurrencyException when concurrency error
     * @throws QueryFailure         when query failure
     * @throws Throwable            when any other error
     */
    public function append(Stream $stream): void;

    /**
     * Delete a stream by stream name.
     *
     * @throws StreamNotFound when stream not found
     * @throws QueryFailure   when query failure
     * @throws Throwable      when any other error
     */
    public function delete(StreamName $streamName): void;

    /**
     * Get the event stream provider.
     */
    public function getEventStreamProvider(): EventStreamProvider;
}
