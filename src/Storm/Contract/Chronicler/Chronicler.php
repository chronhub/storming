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
     * @throws StreamNotFound
     * @throws ConcurrencyException
     * @throws QueryFailure
     * @throws Throwable
     */
    public function append(Stream $stream): void;

    /**
     * @throws StreamNotFound
     * @throws QueryFailure
     * @throws Throwable
     */
    public function delete(StreamName $streamName): void;
}
