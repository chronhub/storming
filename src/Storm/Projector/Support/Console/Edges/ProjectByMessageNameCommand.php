<?php

declare(strict_types=1);

namespace Storm\Projector\Support\Console\Edges;

use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'projector:edge:message-name',
    description: 'Projects all streams events per message name under an internal stream named $by_message_name'
)]
final class ProjectByMessageNameCommand extends ProjectEdgeCommand
{
    protected $signature = 'projector:edge:message-name
                            { connection            : The connection name }
                            { build                 : The build projection ID }
                            { --signal=false        : Trigger the command with signals } 
                            { --in-background=false : Determine if the command should be run in the background }';
}
