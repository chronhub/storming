<?php

declare(strict_types=1);

namespace Storm\Contract\Message;

interface Header
{
    /**
     * The EVENT_ID key represents the unique identifier of the event.
     *
     * Key Characteristics:
     *  1. Immutable: The EVENT_ID key is immutable and should not be changed.
     *  2. String Type: The EVENT_ID key must be unique.
     */
    public const string EVENT_ID = '__event_id';

    /**
     * The EVENT_TYPE key represents the type of the event.
     *
     * Key Characteristics:
     *  1. Immutable: The EVENT_TYPE key is immutable and should not be changed.
     *  2. String Type: The EVENT_TYPE key is a class string of the event.
     */
    public const string EVENT_TYPE = '__event_type';

    /**
     * The EVENT_TIME key represents the point in time when the event was created.
     *
     * Key Characteristics:
     *  1. Immutable: The EVENT_TIME key is immutable and should not be changed.
     *  2. Timestamp Type: The EVENT_TIME key is a string representation of a point in time.
     *
     * Use Cases:
     *  - Recording the time of the event creation.
     *  - Ensuring event ordering and time-based queries, for some contexts
     */
    public const string EVENT_TIME = '__event_time';
}
