<?php

declare(strict_types=1);

namespace Storm\Projector\Support\Console\Edges;

use Illuminate\Console\Command;
use Storm\Contract\Projector\EmitterProjector;
use Storm\Contract\Projector\PersistentProjector;
use Storm\Projector\Support\Builder\EmitterProjectorBuilder;
use Symfony\Component\Console\Command\SignalableCommandInterface;

use function pcntl_async_signals;

abstract class ProjectEdgeCommand extends Command implements SignalableCommandInterface
{
    protected ?EmitterProjector $projector;

    public function handle(): int
    {
        $isDispatchSignal = $this->setupSignalHandler();

        $this->projector = $this->getProjectionBuilder()
            ->enableSignal($isDispatchSignal)
            ->build();

        $this->projector->run($this->shouldKeepRunning());

        return self::SUCCESS;
    }

    protected function getProjectionBuilder(): EmitterProjectorBuilder
    {
        $builder = $this->getLaravel()[$this->argument('build')];

        return $builder($this->argument('connection'));
    }

    public function handleSignal(int $signal): int
    {
        $name = 'query';

        if ($this->projector instanceof PersistentProjector) {
            $name = $this->projector->getName();
        }

        $this->line("Stopping the projection $name...");

        $this->projector->stop();

        return self::SUCCESS;
    }

    public function getSubscribedSignals(): array
    {
        return [SIGINT, SIGTERM];
    }

    protected function shouldKeepRunning(): bool
    {
        return $this->option('in-background') === true;
    }

    protected function setupSignalHandler(): bool
    {
        if ($this->option('signal') === true) {
            pcntl_async_signals(true);

            return true;
        }

        return false;
    }
}
