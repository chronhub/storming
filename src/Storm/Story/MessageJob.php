<?php

declare(strict_types=1);

namespace Storm\Story;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Queue\InteractsWithQueue;
use Storm\Story\Support\MessageType;

class MessageJob
{
    use InteractsWithQueue;
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 1;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     */
    public int $maxExceptions = 1;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 60;

    public ?int $backoff = null;

    public function __construct(
        public string $serviceContext,
        public array $payload,
        array $queue = []
    ) {
        $this->setQueueOptions($queue);
    }

    public function queue(Queue $queue, self $messageJob): void
    {
        $queue->pushOn($this->queue, $messageJob);
    }

    public function displayName(): string
    {
        return MessageType::getEventTypeFromArray($this->payload);
    }

    public function handle(Container $container): void
    {
        /** @var StoryContext $context */
        $context = $container[$this->serviceContext];

        $context($this->payload, $this);
    }

    private function setQueueOptions(array $queue): void
    {
        $this->connection = $queue['connection'] ?? $this->connection;
        $this->queue = $queue['name'] ?? $this->queue;
        $this->tries = $queue['tries'] ?? $this->tries;
        $this->delay = $queue['delay'] ?? $this->delay;
        $this->maxExceptions = $queue['max_exceptions'] ?? $this->maxExceptions;
        $this->timeout = $queue['timeout'] ?? $this->timeout;
        $this->backoff = $queue['backoff'] ?? $this->backoff;
    }
}
