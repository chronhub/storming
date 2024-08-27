<?php

declare(strict_types=1);

namespace Storm\Projector\Support\Console\Monitor;

use Illuminate\Console\Command;
use Storm\Contract\Projector\ProjectorManager;
use Storm\Contract\Projector\ProjectorMonitor;
use Storm\Projector\Exception\InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;
use Throwable;

use function in_array;

#[AsCommand(
    name: 'projector:monitor:mark',
    description: 'Marks a projection as stopping, resetting, deleting or deleting with emitted evens'
)]
final class MarkMonitorProjectionCommand extends Command
{
    public function __construct(private readonly ProjectorManager $manager)
    {
        parent::__construct();
    }

    protected $signature = 'projector:monitor:mark
                            { connection : The connection to use }
                            { operation : stop|reset|delete|deleteWith }
                            { projection : The name of the projection to monitor }
                            { --no_confirmation=false : Do not ask for confirmation }';

    public function handle(): int
    {
        try {
            $monitor = $this->monitor();
            $projection = $this->argument('projection');
            $currentStatus = $this->getStatusOfProjection($monitor, $projection);
            $operation = $this->assertValidOperation();

            $confirmed = $this->handleConfirmation($operation, $currentStatus, $projection);

            if (! $confirmed) {
                $this->components->info('Aborting...');

                return self::SUCCESS;
            }

            match ($operation) {
                'stop' => $monitor->markAsStop($projection),
                'reset' => $monitor->markAsReset($projection),
                'delete' => $monitor->markAsDelete($projection, false),
                'deleteWith' => $monitor->markAsDelete($projection, true),
            };
        } catch (Throwable $e) {
            $this->components->error($e->getCode().': '.$e->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Projection $projection marked as $operation");

        return self::SUCCESS;
    }

    private function handleConfirmation(string $operation, string $currentStatus, string $projection): bool
    {
        if ($this->option('no_confirmation') === true) {
            return true;
        }

        if ($operation === 'deleteWith') {
            $operation = 'delete with emitted events';
        }

        return $this->components->confirm(
            "Are you sure you want to $operation projection $projection with status of $currentStatus?"
        );
    }

    private function getStatusOfProjection(ProjectorMonitor $monitoring, string $projection): string
    {
        return $monitoring->statusOf($projection);
    }

    /**
     * @return string{'stop'|'reset'|'delete'|'deleteWith'}
     */
    private function assertValidOperation(): string
    {
        $operation = $this->argument('operation');

        if (! in_array($operation, ['stop', 'reset', 'delete', 'deleteWith'])) {
            throw new InvalidArgumentException("Invalid operation $operation");
        }

        return $operation;
    }

    private function monitor(): ProjectorMonitor
    {
        return $this->manager->monitor($this->argument('connection'));
    }
}
