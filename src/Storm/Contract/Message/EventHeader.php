<?php

declare(strict_types=1);

namespace Storm\Contract\Message;

interface EventHeader extends Header
{
    public const string AGGREGATE_ID = '__aggregate_id';

    public const string AGGREGATE_ID_TYPE = '__aggregate_id_type';

    public const string AGGREGATE_TYPE = '__aggregate_type';

    public const string AGGREGATE_VERSION = '__aggregate_version';

    public const string INTERNAL_POSITION = '__internal_position';

    public const string EVENT_CAUSATION_ID = '__event_causation_id';

    public const string EVENT_CAUSATION_TYPE = '__event_causation_type';
}
