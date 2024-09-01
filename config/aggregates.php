<?php

declare(strict_types=1);

return [

    /**
     * Repository instances configuration.
     *
     *  - name: the name of the repository, use to create instance through the manager.
     *  - stream_name: the stream name to use for the repository.
     *  - chronicler: the chronicler service ID to use for the repository.
     *  - event_decorator: the event decorator service ID to use for the repository.
     *    It should implement ay least the default decorator configured in storm.decorators.event.
     *  - use_clock: if true, the repository will set the clock on the aggregate
     *    which implements ClockAware.
     *
     *    @see \Storm\Contract\Clock\ClockAware
     *
     *  - cache: the cache configuration to use for the repository.
     *    Set to null/false to disable caching, an empty array will use the default cache system.
     */
    'repositories' => [

        'name' => [
            'stream_name' => 'your_stream_name',
            'chronicler' => 'service_id',
            'event_decorator' => 'storm.event_decorator.chain',
            'use_clock' => true, // checkMe if set, the cache serialize the aggregate with clock instance
            'cache' => [
                'prefix' => 'your_stream_name', // default to 'your_stream_name'
                'ttl' => 3600, // default 3600 in seconds
                'store' => 'redis', // if null, it will use your default system cache
            ],
        ],
    ],

];
