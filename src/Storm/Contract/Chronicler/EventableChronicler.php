<?php

declare(strict_types=1);

namespace Storm\Contract\Chronicler;

use Storm\Contract\Tracker\Listener;
use Storm\Contract\Tracker\StreamTracker;

interface EventableChronicler extends ChroniclerDecorator
{
    public const string APPEND_STREAM_EVENT = 'append_stream';

    public const string DELETE_STREAM_EVENT = 'delete_stream';

    public const string ALL_STREAM_EVENT = 'all_stream';

    public const string ALL_BACKWARDS_STREAM_EVENT = 'all_backwards_stream';

    public const string FILTERED_STREAM_EVENT = 'filtered_stream';

    public const string FILTER_STREAM_EVENT = 'filter_streams';

    public const string FILTER_CATEGORY_EVENT = 'filter_categories';

    public const string HAS_STREAM_EVENT = 'has_stream';

    /**
     * Subscribe to an event.
     */
    public function subscribe(string $eventName, callable $streamContext, int $priority = 0): Listener;

    /**
     * Unsubscribe from an event.
     */
    public function unsubscribe(Listener ...$eventSubscribers): void;

    /**
     * Get the stream tracker.
     */
    public function getStreamTracker(): StreamTracker;
}
