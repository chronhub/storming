<?php

declare(strict_types=1);

namespace Storm\Projector\Support\Console\Edges;

use Storm\Contract\Projector\EmitterProjector;
use Storm\Contract\Projector\ProjectorManagerInterface;
use Storm\Projector\Scope\EmitterScope;
use Storm\Projector\Stream\Filter\InMemoryFromToPosition;
use Storm\Stream\StreamName;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'projector:edge:partition',
    description: 'Projects partitions streams events under an internal stream named prefix with $ct-'
)]
final class ProjectByPartitionCommand extends ProjectEdgeCommand
{
    protected $signature = 'projector:edge:partition
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
            streamName: '$by_partition',
            options: ['signal' => $isDispatchSignal],
            connection: $this->argument('connection'),
        );

        $this->projector
            ->filter(new InMemoryFromToPosition())
            ->subscribeToAll()
            ->when([],
                function (EmitterScope $scope): void {
                    $currentStreamName = $scope->streamName();
                    $streamName = new StreamName($currentStreamName);

                    if (! $streamName->hasPartition()) {
                        return;
                    }

                    $scope->linkTo('$ct-'.$streamName->partition(), $scope->event());
                })
            ->run($this->shouldRunInBackground());

        return self::SUCCESS;
    }

    protected function getProjector(): EmitterProjector
    {
        return $this->projector;
    }
}
