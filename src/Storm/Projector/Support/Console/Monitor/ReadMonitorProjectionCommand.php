<?php

declare(strict_types=1);

namespace Storm\Projector\Support\Console\Monitor;

use Illuminate\Console\Command;
use Storm\Contract\Projector\ProjectorManager;
use Storm\Contract\Projector\ProjectorMonitor;
use Storm\Projector\Exception\InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;
use Throwable;

use function is_array;
use function is_bool;
use function is_null;
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

    public function __construct(private readonly ProjectorManager $manager)
    {
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
            $this->components->error($e->getCode().': '.$e->getMessage()); // fixMe component getCode string|int

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function operate(ProjectorMonitor $monitoring, string $operation, string $projection): void
    {
        $result = match ($operation) {
            'state' => $monitoring->stateOf($projection),
            'status' => $monitoring->statusOf($projection),
            'checkpoint' => $monitoring->checkpointOf($projection),
            default => throw new InvalidArgumentException("Invalid operation [$operation] for projection [$projection]"),
        };

        $this->line("\n<info>Operation:</info> $operation");
        $this->line("<info>Projection:</info> $projection\n");

        if (is_array($result)) {
            if ($operation === 'checkpoint') {
                $this->displayCheckpoint($result);
            } else {
                $this->displayTable($result);
            }
        } else {
            $this->info($result);
        }
    }

    private function displayCheckpoint(array $checkpoints): void
    {
        foreach ($checkpoints as $index => $checkpoint) {
            $this->line('<info>Checkpoint #'.($index + 1).':</info>');

            $this->displayTable($checkpoint);

            $this->line('');
        }
    }

    private function displayTable(array $data): void
    {
        $map = collect($data)->map(fn ($value, $key) => [$key, $this->formatValue($value)]);

        $this->table(['Key', 'Value'], $map);
    }

    private function formatValue(mixed $value): string
    {
        return match (true) {
            is_array($value) => $value === [] ? '[]' : json_encode($value, JSON_PRETTY_PRINT),
            is_null($value) => 'null',
            is_bool($value) => $value ? 'true' : 'false',
            default => (string) $value,
        };
    }

    private function monitor(): ProjectorMonitor
    {
        return $this->manager->monitor($this->argument('connection'));
    }
}
