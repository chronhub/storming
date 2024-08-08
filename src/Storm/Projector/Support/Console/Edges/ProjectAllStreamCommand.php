<?php

declare(strict_types=1);

namespace Storm\Projector\Support\Console\Edges;

use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'projector:edge:all',
    description: 'Projects all streams events under an internal stream named $all'
)]
final class ProjectAllStreamCommand extends ProjectEdgeCommand
{
    protected $signature = 'projector:edge:all 
                            { connection            : The connection name }
                            { build                 : The build projection ID }
                            { --signal=false         : Trigger the command with signals } 
                            { --in-background=false : Determine if the command should be run in the background }';
}
