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
     *    It should implement at least the default decorator configured in storm.decorators.event.
     *  - cache: the cache configuration to use for the repository.
     *    Set to null/false to disable caching, an empty array will use the default cache system.
     */
    'repositories' => [

        'name' => [
            'stream_name' => 'your_stream_name',
            'chronicler' => 'service_id',
            'event_decorator' => 'storm.event_decorator.chain',
            'cache' => [
                'store' => 'redis', // if null, it will use your default system cache
                'prefix' => 'your_stream_name', // default to 'your_stream_name'
                'ttl' => 3600, // default 3600 in seconds
            ],
        ],
    ],

];
