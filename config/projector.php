<?php

declare(strict_types=1);

use Storm\Projector\Options\DefaultOption;
use Storm\Projector\Options\InMemoryOption;
use Storm\Projector\Stream\Filter\FromIncludedPosition;
use Storm\Projector\Stream\Filter\InMemoryFromToPosition;
use Storm\Projector\Support\Console\Monitor\MarkMonitorProjectionCommand;
use Storm\Projector\Support\Console\Monitor\ReadMonitorProjectionCommand;

return [
    'default' => 'in_memory',

    'connection' => [

        'pgsql' => [
            'connection' => 'pgsql',
            'table_name' => null,
            'chronicler' => 'pgsql', // key in chronicler.store.connection
            //'event_stream_provider' => 'pgsql',
            'query_filter' => FromIncludedPosition::class,
            'options' => DefaultOption::class,
            'serializer' => 'json',
            'dispatch_events' => false,
        ],

        'hybrid' => [
            'connection' => 'mysql',
            'table_name' => null,
            'chronicler' => 'pgsql', // key in chronicler.store.connection
            'query_filter' => FromIncludedPosition::class,
            'options' => DefaultOption::class,
            'serializer' => 'json',
            'dispatch_events' => false,
        ],

        'in_memory' => [
            'chronicler' => 'in_memory', // todo
            'query_filter' => InMemoryFromToPosition::class,
            'options' => InMemoryOption::class,
            'serializer' => 'json',
            'dispatch_events' => false,
        ],

        'in_memory-incremental' => [
            'chronicler' => 'in_memory.incremental', // todo
            'query_filter' => InMemoryFromToPosition::class,
            'options' => InMemoryOption::class,
            'serializer' => 'json',
            'dispatch_events' => false,
        ],
    ],

    /**
     * Serializer configuration.
     * Merge with storm serializer configuration
     *
     * @see \Storm\Serializer\\Storm\Serializer\SerializerFactory
     */
    'serializer' => [
        'json' => [
            'driver' => 'json',
            'normalizers' => [],
            'context' => [],
            'encode_options' => JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_FORCE_OBJECT,
            'decode_options' => JSON_OBJECT_AS_ARRAY | JSON_BIGINT_AS_STRING,
        ],
    ],

    /**
     * All the projection builds here.
     *
     * When auto discovery is enabled,
     * it will produce abstract as 'projection.emitter.edge-all'
     */
    'projections' => [

        'auto_discovery' => false,

        'projection' => [
            'read_model' => [],

            'emitter' => [],

            'query' => [],
        ],
    ],

    'console' => [
        'commands' => [
            // Monitor projections
            ReadMonitorProjectionCommand::class,
            MarkMonitorProjectionCommand::class,
        ],
    ],
];
