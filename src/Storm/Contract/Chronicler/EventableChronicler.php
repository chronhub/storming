<?php

declare(strict_types=1);

namespace Storm\Contract\Chronicler;

use Storm\Chronicler\Tracker\Listener;
use Storm\Chronicler\Tracker\ListenerOnce;

interface EventableChronicler extends ChroniclerDecorator
{
    final public const string APPEND_STREAM = 'stream.append';

    final public const string DELETE_STREAM = 'stream.delete';

    final public const string RETRIEVE_ALL = 'stream.retrieve.all';

    final public const string RETRIEVE_ALL_REVERSED = 'stream.retrieve.all.reversed';

    final public const string RETRIEVE_FILTERED = 'stream.retrieve.filtered';

    final public const string FILTER_STREAMS = 'stream.filter.streams';

    final public const string FILTER_PARTITIONS = 'stream.filter.partitions';

    final public const string STREAM_EXISTS = 'stream.exists';

    /**
     * Subscribe to the event store
     */
    public function subscribe(string $eventName, callable $callback, int $priority = 0): Listener;

    /**
     * Subscribe to the event store once
     */
    public function subscribeOnce(string $eventName, callable $callback, int $priority = 0): ListenerOnce;

    /**
     * Dispatch an event to the event store
     */
    public function unsubscribe(Listener ...$listeners): void;
}
