<?php

declare(strict_types=1);

namespace Storm\Story\Console;

use Illuminate\Console\Command;
use Storm\Story\Build\MessageHandlerMetadata;
use Storm\Story\Build\MessageServiceLocator;
use Symfony\Component\Console\Attribute\AsCommand;

use function in_array;

//fixMe to fit new metadata
#[AsCommand(name: 'storm:story:messages', description: 'List all or per type or find registered messages')]
class ListStoryMessageCommand extends Command
{
    protected array $tableHeaders = ['Message', 'Handler', 'Method', 'Queue', 'Priority'];

    protected $signature = 'storm:story:messages
                            { type :  The type of the message (all|command|event|query) }
                            { --find=0  Find the message by name }';

    public function __construct(protected MessageServiceLocator $serviceLocator)
    {
        parent::__construct();
    }

    public function __invoke(): int
    {
        if ($this->option('find') === '1') {
            $this->handleCompletion();

            return self::SUCCESS;
        }

        $this->buildTable($this->argument('type'));

        return self::SUCCESS;
    }

    protected function handleCompletion(): void
    {
        $messages = collect($this->serviceLocator->all());

        $formattedMessages = $messages->keys()
            ->map(fn (string $name) => [class_basename($name), $name])
            ->sortBy(fn (array $message) => $message[0])
            ->values()
            ->all();

        /** @var @phpstan-ignore-next-line */
        $messageLookup = collect($formattedMessages)->pluck(1, 0);

        $selected = $this->components->askWithCompletion('Find message', $messageLookup->keys()->toArray());

        $selectedMessageDetails = $messageLookup->get($selected);

        if ($selectedMessageDetails === null) {
            $this->warn('Message not found.');

            return;
        }

        $details = $messages->get($selectedMessageDetails);
        $rows = collect($details)->map(fn ($detail) => $this->getRow($detail, $selectedMessageDetails))->all();

        $this->table($this->tableHeaders, $rows);
    }

    protected function buildTable(string $type): void
    {
        if (! in_array($type, ['all', 'command', 'event', 'query'], true)) {
            $this->error('Invalid type. Use "all", "command", "event" or "query".');

            return;
        }

        $messages = collect($this->serviceLocator->all());

        $list = $messages->flatMap(function (array $handlers, string $name) use ($type) {
            return collect($handlers)
                ->filter(fn (MessageHandlerMetadata $handler) => $type === 'all' || $handler->type === $type)
                ->map(fn (MessageHandlerMetadata $handler) => $this->getRow($handler, $name));
        })->all();

        $this->table($this->tableHeaders, $list);
    }

    protected function getRow(MessageHandlerMetadata $metadata, string $name, bool $shortClass = true): array
    {
        return [
            $shortClass ? class_basename($name) : $name,
            $shortClass ? class_basename($metadata->handler) : $metadata->handler,
            $metadata->method,
            $metadata->queue,
            $metadata->priority,
        ];
    }
}
