<?php

declare(strict_types=1);

namespace Storm\Projector\Support\Console\Edges;

use Storm\Contract\Projector\EmitterProjector;
use Storm\Contract\Projector\ProjectorManagerInterface;
use Storm\Projector\Scope\EmitterScope;
use Storm\Projector\Stream\Filter\InMemoryFromToPosition;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'projector:edge:all',
    description: 'Projects all streams events under an internal stream named $all'
)]
final class ProjectAllStreamCommand extends ProjectEdgeCommand
{
    protected $signature = 'projector:edge:all 
                            { connection            : The connection name }
                            { --signal=true         : Trigger the command with signals } 
                            { --in-background=false : Determine if the command should be run in the background }';

    private ?EmitterProjector $projector;

    public function __construct(
        private readonly ProjectorManagerInterface $manager
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $isDispatchSignal = $this->setupSignalHandler();

        $this->projector = $this->manager->newEmitterProjector(
            streamName: '$all',
            options: ['signal' => $isDispatchSignal],
            connection: $this->argument('connection'),
        );

        $this->projector
            ->filter(new InMemoryFromToPosition())
            ->subscribeToAll()
            ->when([], function (EmitterScope $scope): void {
                $scope->emit($scope->event());
            })
            ->run($this->shouldRunInBackground());

        return self::SUCCESS;
    }

    protected function getProjector(): EmitterProjector
    {
        return $this->projector;
    }
}
