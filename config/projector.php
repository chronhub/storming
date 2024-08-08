<?php

declare(strict_types=1);

use Storm\Projector\Options\DefaultOption;
use Storm\Projector\Options\InMemoryOption;
use Storm\Projector\Stream\Filter\FromIncludedPosition;
use Storm\Projector\Stream\Filter\InMemoryFromToPosition;
use Storm\Projector\Support\Builds\ProjectAllStreamBuild;
use Storm\Projector\Support\Builds\ProjectByMessageNameBuild;
use Storm\Projector\Support\Builds\ProjectByPartitionBuild;
use Storm\Projector\Support\Console\Edges\ProjectAllStreamCommand;
use Storm\Projector\Support\Console\Edges\ProjectByMessageNameCommand;
use Storm\Projector\Support\Console\Edges\ProjectByPartitionCommand;
use Storm\Projector\Support\Console\MarkMonitorProjectionCommand;
use Storm\Projector\Support\Console\ReadMonitorProjectionCommand;

return [
    'default' => 'in_memory',

    'connection' => [

        'in_memory' => [
            'provider' => 'projector.provider.in_memory',
            'chronicler' => 'chronicler.in_memory',
            'chronicler.provider' => 'chronicler.provider.in_memory',
            'serializer' => 'projector.serializer.json',
            'options' => InMemoryOption::class,
            'query_filter' => InMemoryFromToPosition::class,
            'dispatch_events' => false,
        ],

        'in_memory-incremental' => [
            'provider' => 'projector.provider.in_memory',
            'chronicler' => 'chronicler.in_memory.incremental',
            'chronicler.provider' => 'chronicler.provider.in_memory',
            'serializer' => 'projector.serializer.json',
            'options' => InMemoryOption::class,
            'query_filter' => InMemoryFromToPosition::class,
            'dispatch_events' => false,
        ],

        'pgsql' => [
            'provider' => 'projector.provider.database',
            'options' => DefaultOption::class,
            'chronicler' => 'chronicler.database',
            'chronicler.provider' => 'chronicler.provider.database',
            'serializer' => 'projector.serializer.json',
            'filter' => FromIncludedPosition::class,
            'dispatch_events' => false,
        ],
    ],
    /**
     * All the projections build here.
     * When auto discovery is enabled,
     * it will produce abstract as 'projection.emitter.edge-all'
     */
    'projections' => [
        'auto_discovery' => true,
        'projection' => [
            'read_model' => [

            ],
            'emitter' => [
                'edge-all' => ProjectAllStreamBuild::class,
                'edge-message-name' => ProjectByMessageNameBuild::class,
                'edge-partition' => ProjectByPartitionBuild::class,
            ],
            'query' => [

            ],
        ],
    ],

    'console' => [
        'commands' => [
            // Monitor projections
            ReadMonitorProjectionCommand::class,
            MarkMonitorProjectionCommand::class,

            // Edges projection require a projection builder
            ProjectByPartitionCommand::class,
            ProjectAllStreamCommand::class,
            ProjectByMessageNameCommand::class,
        ],
    ],
];
