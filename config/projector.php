<?php

declare(strict_types=1);

return [
    'default' => 'in_memory',

    'connections' => [
        'in_memory' => [
            'default' => [
                'factory' => \Storm\Projector\Factory\InMemorySubscriptionFactory::class,
                'event_store_id' => 'from event store config',
                'event_store_provider' => 'from event store config',
                'options' => [], // service id or projection option class
                'projection_provider' => \Storm\Projector\Repository\InMemoryProvider::class,
                'use_events' => false,
            ],
        ],

        'database' => [
            'pgsql' => [
                'factory' => \Storm\Projector\Factory\DatabaseSubscriptionFactory::class,
                'event_store_id' => 'from event store config',
                'event_store_provider' => 'from event store config',
                'options' => [], // service id or projection option class
                'projection_provider' => \Storm\Projector\Repository\DatabaseProvider::class,
                'use_events' => false,
            ],
        ],
    ],
];
