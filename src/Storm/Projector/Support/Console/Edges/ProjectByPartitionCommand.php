<?php

declare(strict_types=1);

namespace Storm\Projector\Support\Console\Edges;

use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'projector:edge:partition',
    description: 'Projects partitions streams events under an internal stream named prefix with $ct-'
)]
final class ProjectByPartitionCommand extends ProjectEdgeCommand
{
    protected $signature = 'projector:edge:partition
                            { connection            : The connection name }
                            { build                 : The build projection ID }
                            { --signal=true         : Trigger the command with signals } 
                            { --in-background=false : Determine if the command should be run in the background }';
}
