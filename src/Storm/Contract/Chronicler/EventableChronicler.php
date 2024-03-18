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
    public const APPEND_STREAM_EVENT = 'append_stream';

    /**
     * @var string
     */
    public const DELETE_STREAM_EVENT = 'delete_stream';

    /**
     * @var string
     */
    public const ALL_STREAM_EVENT = 'all_stream';

    /**
     * @var string
     */
    public const ALL_BACKWARDS_STREAM_EVENT = 'all_backwards_stream';

    /**
     * @var string
     */
    public const FILTERED_STREAM_EVENT = 'filtered_stream';

    /**
     * @var string
     */
    public const FILTER_STREAM_EVENT = 'filter_streams';

    /**
     * @var string
     */
    public const FILTER_CATEGORY_EVENT = 'filter_categories';

    /**
     * @var string
     */
    public const HAS_STREAM_EVENT = 'has_stream';

    public function subscribe(string $eventName, callable $streamContext, int $priority = 0): Listener;

    public function unsubscribe(Listener ...$eventSubscribers): void;

    public function getStreamTracker(): StreamTracker;
}
