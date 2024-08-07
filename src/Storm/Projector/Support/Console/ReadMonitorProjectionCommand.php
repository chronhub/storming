<?php

declare(strict_types=1);

namespace Storm\Projector\Support\Console;

use Illuminate\Console\Command;
use Storm\Contract\Projector\Monitoring;
use Storm\Contract\Projector\MonitoringManager;
use Storm\Projector\Exception\InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;
use Throwable;

use function json_encode;

#[AsCommand(
    name: 'projector:monitor:read',
    description: 'Monitor the read side of a projection (state, status or checkpoint)'
)]
final class ReadMonitorProjectionCommand extends Command
{
    protected $signature = 'projector:monitor:read
                            { connection : The connection to use }
                            { operation : state|status|checkpoint }
                            { projection : The name of the projection to monitor }';

    public function __construct(
        private readonly MonitoringManager $manager
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->components->info('Reading projection...');

        try {
            $this->operate(
                $this->monitor(),
                $this->argument('operation'),
                $this->argument('projection')
            );
        } catch (Throwable $e) {
            $this->components->error($e->getCode().': '.$e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * @todo prettier output
     */
    private function operate(Monitoring $monitoring, string $operation, string $projection): void
    {
        switch ($operation) {
            case 'state':
                $this->info('State of projection '.$projection.': '.json_encode($monitoring->stateOf($projection)));

                break;
            case 'status':
                $this->info('Status of projection '.$projection.': '.$monitoring->statusOf($projection));

                break;
            case 'checkpoint':
                $this->info('Checkpoint of projection '.$projection.': '.json_encode($monitoring->checkpointOf($projection)));

                break;
            default:
                throw new InvalidArgumentException("Invalid operation $operation for $projection");
        }
    }

    private function monitor(): Monitoring
    {
        return $this->manager->monitor($this->argument('connection'));
    }
}
