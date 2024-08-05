<?php

declare(strict_types=1);

return [
    'default' => 'in_memory',

    'connection' => [
        'in_memory' => [
            'provider' => 'projector.provider.in_memory',
            'options' => \Storm\Projector\Options\InMemoryOption::class,
            'chronicler' => 'chronicler.in_memory',
            'chronicler.provider' => 'chronicler.provider.in_memory',
            'serializer' => 'projector.serializer.json',
            'dispatch_events' => false,
        ],

        'pgsql' => [
            'provider' => 'projector.provider.database',
            'options' => \Storm\Projector\Options\DefaultOption::class,
            'chronicler' => 'chronicler.database',
            'chronicler.provider' => 'chronicler.provider.database',
            'serializer' => 'projector.serializer.json',
            'dispatch_events' => false,
        ],
    ],
];
