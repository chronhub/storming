<?php

declare(strict_types=1);

namespace Storm\Projector\Support\Console\Edges;

use Illuminate\Console\Command;
use Storm\Contract\Projector\EmitterProjector;
use Symfony\Component\Console\Command\SignalableCommandInterface;

use function pcntl_async_signals;

abstract class ProjectEdgeCommand extends Command implements SignalableCommandInterface
{
    public function getSubscribedSignals(): array
    {
        return [SIGINT, SIGTERM];
    }

    public function handleSignal(int $signal): int
    {
        $projectionName = $this->getProjector()->getName();
        $this->line("Stopping the projection $projectionName...");

        $this->getProjector()->stop();

        return self::SUCCESS;
    }

    protected function shouldRunInBackground(): bool
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

    abstract protected function getProjector(): EmitterProjector;
}
