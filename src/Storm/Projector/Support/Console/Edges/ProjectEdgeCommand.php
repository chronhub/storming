<?php

declare(strict_types=1);

namespace Storm\Projector\Support\Console\Edges;

use Illuminate\Console\Command;
use Storm\Contract\Projector\EmitterProjector;
use Storm\Contract\Projector\PersistentProjector;
use Storm\Projector\Support\Builder\EmitterProjectorBuilder;
use Symfony\Component\Console\Command\SignalableCommandInterface;

use function assert;
use function extension_loaded;
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
        $build = $this->argument('build');
        $connection = $this->argument('connection');

        $builder = $this->getLaravel()[$build];

        return $builder($connection);
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
        //fixMe phpstorm does not recognize the projector composer.json
        assert(extension_loaded('pcntl'));

        if ($this->option('signal') === true) {
            pcntl_async_signals(true);

            return true;
        }

        return false;
    }
}
