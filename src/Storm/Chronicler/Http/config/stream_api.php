<?php

declare(strict_types=1);

return [

    'stream' => [
        'name' => 'stream-api',
        'server' => 'http://localhost:8000',
        'prefix' => 'stream',
        'chronicler' => 'chronicler.api.standard',
    ],

    'projection' => [
        'name' => 'projection-api',
        'server' => 'http://localhost:8000',
        'prefix' => 'projection',
        'chronicler' => 'chronicler.api.standard',
    ],
];
