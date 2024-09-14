<?php

declare(strict_types=1);

return [

    /**
     * Event stream provider configuration.
     */
    'provider' => [
        'connection' => [
            'in_memory' => 'chronicler.in_memory', // todo
            'pgsql' => 'event_stream.provider.db.pgsql',
        ],
    ],

    /**
     * Event store configuration.
     */
    'store' => [
        'connection' => [
            'in_memory' => [
                // todo
            ],

            'pgsql' => [
                'connection' => 'pgsql',
                'table_name' => null,
                'provider' => 'pgsql',
                'serializer' => 'json',
                'loader' => null,
            ],

            'transactional' => [
                'connection' => 'pgsql',
                'table_name' => null,
                'provider' => 'pgsql',
                'serializer' => 'json',
                'loader' => null,
            ],

            // todo
            'outbox' => [
                'connection' => 'pgsql',
                'table_name' => null,
                'provider' => 'pgsql',
                'serializer' => 'json',
                'loader' => null,
            ],

            'publisher' => [
                'connection' => 'pgsql',
                'table_name' => null,
                'provider' => 'pgsql',
                'serializer' => 'json',
                'queue' => [
                    'job' => \Storm\Chronicler\Publisher\OutboxQueue::class,
                    'connection' => null,
                    'queue' => 'outbox',
                ],
                'loader' => null,
            ],

            // todo connector support
            'api' => [
                'connection' => 'pgsql',
                'table_name' => null,
                'provider' => 'pgsql',
                'serializer' => 'todo', // make a connector for this serializer
                'loader' => null,
            ],
        ],
    ],

    /**
     * Serializer configuration.
     *
     * @see \Storm\Serializer\JsonSerializerFactory
     */
    'serializer' => [

        'json' => [
            'driver' => 'json',
            'normalizers' => [
                \Storm\Serializer\StreamEventNormalizer::class,
            ],
            'context' => [],
            'encode_options' => null,
            'decode_options' => null,
        ],
    ],
];
