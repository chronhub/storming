<?php

declare(strict_types=1);

namespace Storm\Contract\Message;

interface EventHeader extends Header
{
    public const string AGGREGATE_ID = '__aggregate_id';

    public const string AGGREGATE_ID_TYPE = '__aggregate_id_type';

    public const string AGGREGATE_TYPE = '__aggregate_type';

    public const string AGGREGATE_VERSION = '__aggregate_version';

    /**
     * Internal Event Position Representation
     *
     * The INTERNAL_POSITION constant represents a key used to store the original
     * position of an event within its source stream.
     *
     * Key Characteristics:
     * 1. Deserialization Context: This position is only populated when an event
     *    is deserialized from storage, not for newly created events.
     *
     * 2. Positional Relationships:
     *    a. Regular Streams: In standard event streams, this position typically
     *       corresponds to the incremented position of the event.
     *    B. Emitters or Partitioned Streams: this position becomes crucial
     *       as it preserves the original position of the event in its source stream.
     *
     * Use Cases:
     * - Maintaining event order integrity across stream manipulations.
     * - Facilitating accurate event replay and reconstruction.
     * - Supporting advanced stream operations like merging or partitioning.
     *
     * Implementation Note:
     * This key should be considered as read-only and only manipulated by the system.
     */
    public const string INTERNAL_POSITION = '__internal_position';

    public const string EVENT_CAUSATION_ID = '__event_causation_id';

    public const string EVENT_CAUSATION_TYPE = '__event_causation_type';
}
