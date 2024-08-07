<?php

declare(strict_types=1);

namespace Storm\Projector\Support\Console\Edges;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Storm\Contract\Projector\EmitterProjector;
use Storm\Contract\Projector\ProjectorManagerInterface;
use Storm\Projector\Scope\EmitterScope;
use Storm\Projector\Stream\Filter\InMemoryFromToPosition;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\SignalableCommandInterface;

use function pcntl_async_signals;

#[AsCommand(
    name: 'projector:edge:message-name',
    description: 'Projects all streams events per message name under an internal stream named $by_message_name'
)]
final class ProjectByMessageNameCommand extends Command implements SignalableCommandInterface
{
    protected $signature = 'projector:edge:message-name
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
            streamName: '$by_message_name',
            options: ['signal' => $isDispatchSignal],
            connection: $this->argument('connection'),
        );

        $this->projector
            ->filter(new InMemoryFromToPosition())
            ->subscribeToAll()
            ->when([], function (EmitterScope $scope): void {
                $event = $scope->event();
                $eventClass = '$mn-'.Str::replace('\\', '_', $event::class);

                $scope->linkTo($eventClass, $scope->event());
            })
            ->run($this->shouldRunInBackground());

        return self::SUCCESS;
    }

    public function getSubscribedSignals(): array
    {
        return [SIGINT, SIGTERM];
    }

    public function handleSignal(int $signal): void
    {
        // TODO: Implement handleSignal() method.
        // fixMe persistent projector should be able to stop
    }

    private function shouldRunInBackground(): bool
    {
        return $this->option('in-background') === true;
    }

    private function setupSignalHandler(): bool
    {
        if ($this->option('signal') === true) {
            pcntl_async_signals(true);

            return true;
        }

        return false;
    }
}
