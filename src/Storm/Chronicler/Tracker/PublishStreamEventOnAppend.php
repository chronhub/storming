<?php

declare(strict_types=1);

namespace Storm\Chronicler\Tracker;

use Closure;
use Illuminate\Contracts\Bus\Dispatcher;
use Storm\Contract\Message\DomainEvent;
use Storm\Stream\Stream;

final readonly class PublishStreamEventOnAppend
{
    public function __construct(
        private Dispatcher $dispatcher,
        private string $job,
        private ?string $connection = null,
        private ?string $queue = null
    ) {}

    public function __invoke(): Closure
    {
        return function (Stream $stream): void {
            /** @var DomainEvent $event */
            foreach ($stream->events() as $event) {
                $this->dispatcher->dispatch(
                    $this->newJob($event)
                );
            }
        };
    }

    private function newJob(DomainEvent $event): object
    {
        return new $this->job($event, $this->connection, $this->queue);
    }
}
