<?php

declare(strict_types=1);

namespace Storm\Reporter\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Storm\Annotation\KernelStorage;
use Storm\Contract\Reporter\Reporter;
use Storm\Contract\Tracker\Listener;
use Symfony\Component\Console\Attribute\AsCommand;
use Throwable;

use function sprintf;

#[AsCommand(
    name: 'storm:reporter-listener',
    description: 'Display the reporter listeners by reporter id',
)]
class MapReporterListenerCommand extends Command
{
    public const array TABLE_HEADERS = ['Event', 'Origin', 'Priority', 'Listener'];

    protected $signature = 'storm:reporter-listener
                            { id?        : reporter id }
                            { --choice=1 : request for choice }';

    public function __invoke(): int
    {
        try {
            $reporter = $this->getReporter(
                $this->handleCompletionName()
            );
        } catch (Throwable $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $listeners = $reporter->tracker()->listeners();

        // todo prettier
        $this->components->twoColumnDetail(
            sprintf('Reporter class: %s', $reporter::class),
            sprintf('Total: %d', $listeners->count())
        );

        $this->table(self::TABLE_HEADERS, $this->formatTableData($listeners));

        return self::SUCCESS;
    }

    protected function formatTableData(Collection $listeners): array
    {
        return $listeners
            ->groupBy(fn (Listener $listener) => $listener->name())
            ->map(function (Collection $group) {
                return $group
                    ->sortByDesc(fn (Listener $listener) => $listener->priority())
                    ->map(function (Listener $listener) {
                        return [
                            $listener->name(),
                            $listener->origin(),
                            $listener->priority(),
                            $listener::class,
                        ];
                    });
            })
            ->collapse()
            ->toArray();
    }

    protected function handleCompletionName(): string
    {
        $argumentName = $this->argument('id');

        if ($argumentName) {
            return $argumentName;
        }

        if ($this->option('choice') === '1') {
            $name = $this->components->choice('Find reporter by id', $this->findReporterIds());
        }

        return $name ?? throw new InvalidArgumentException('Reporter id not found or not provided');
    }

    /**
     * @return array<string>
     */
    protected function findReporterIds(): array
    {
        return $this->kernel()->getReporters()->keys()->toArray();
    }

    protected function getReporter(string $reporterId): Reporter
    {
        return $this->laravel[$reporterId];
    }

    protected function kernel(): KernelStorage
    {
        return $this->laravel[KernelStorage::class];
    }
}
