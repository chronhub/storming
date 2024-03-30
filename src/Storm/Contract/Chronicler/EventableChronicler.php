<?php

declare(strict_types=1);

namespace Storm\Contract\Chronicler;

use Storm\Contract\Tracker\Listener;
use Storm\Contract\Tracker\StreamTracker;

interface EventableChronicler extends ChroniclerDecorator
{
    /**
     * @var string
     */
    public const string APPEND_STREAM_EVENT = 'append_stream';

    /**
     * @var string
     */
    public const string DELETE_STREAM_EVENT = 'delete_stream';

    /**
     * @var string
     */
    public const string ALL_STREAM_EVENT = 'all_stream';

    /**
     * @var string
     */
    public const string ALL_BACKWARDS_STREAM_EVENT = 'all_backwards_stream';

    /**
     * @var string
     */
    public const string FILTERED_STREAM_EVENT = 'filtered_stream';

    /**
     * @var string
     */
    public const string FILTER_STREAM_EVENT = 'filter_streams';

    /**
     * @var string
     */
    public const string FILTER_CATEGORY_EVENT = 'filter_categories';

    /**
     * @var string
     */
    public const string HAS_STREAM_EVENT = 'has_stream';

    public function subscribe(string $eventName, callable $streamContext, int $priority = 0): Listener;

    public function unsubscribe(Listener ...$listeners): void;

    public function getStreamTracker(): StreamTracker;
}
