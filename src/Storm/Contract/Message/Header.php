<?php

declare(strict_types=1);

namespace Storm\Contract\Message;

interface Header
{
    public const string EVENT_ID = '__event_id';

    public const string EVENT_TYPE = '__event_type';

    public const string EVENT_TIME = '__event_time';

    public const string REPORTER_ID = '__reporter_id';

    public const string EVENT_STRATEGY = '__event_strategy';

    public const string EVENT_DISPATCHED = '__event_dispatched';

    public const string QUEUE = '__queue';
}
