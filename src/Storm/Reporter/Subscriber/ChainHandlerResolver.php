<?php

declare(strict_types=1);

namespace Storm\Reporter\Subscriber;

use Illuminate\Support\Collection;
use RuntimeException;
use Storm\Message\Attribute\MessageHandler;

use function count;

class ChainHandlerResolver
{
    /**
     * @var Collection<QueueData>
     */
    protected Collection $queues;

    /**
     * @var array<MessageHandler>|array
     */
    protected array $syncHandlers = [];

    protected ?MessageHandler $asyncHandler = null;

    public function __construct(
        private readonly Collection $messageHandlers,
        array $queues,
    ) {
        $this->queues = $this->normalizeQueues($queues);
    }

    /**
     * Chain handlers and dispatch the first async handler.
     */
    public function handle(bool $alreadyDispatched): self
    {
        $this->assertNotAlreadyCompleted();

        return $this->firstToHandleWhen($alreadyDispatched)->chainSync()->nextAsync();
    }

    /**
     * Chain sequentially sync handlers and stop when the next message handler is async.
     *
     * @return $this
     */
    protected function chainSync(): self
    {
        $this->queues
            ->skipUntil(fn (QueueData $queue): bool => $queue->isNew())
            ->takeUntil(function (QueueData $queue): bool {
                $messageHandler = $this->getMessageHandlerByPriority($queue->priority);

                return $messageHandler->queue() !== null;
            })
            ->each(function (QueueData $queue): void {
                $messageHandler = $this->getMessageHandlerByPriority($queue->priority);

                $queue->markAsHandled();
                $queue->markAsDispatched();

                $this->syncHandlers[] = $messageHandler;
            });

        return $this;
    }

    /**
     * Find the next async handler to dispatch.
     *
     * @return $this
     */
    protected function nextAsync(): self
    {
        $this->queues
            ->skipUntil(function (QueueData $queue): bool {
                if (! $queue->isNew()) {
                    return false;
                }

                return $this->getMessageHandlerByPriority($queue->priority)->queue() !== null;
            })
            ->first(function (QueueData $queue): bool {
                $messageHandler = $this->getMessageHandlerByPriority($queue->priority);

                $this->setAsyncHandler($messageHandler);

                $queue->markAsDispatched();

                return true;
            });

        return $this;
    }

    /**
     * Find the first handler to handle when it has been dispatched async.
     *
     * @return $this
     */
    protected function firstToHandleWhen(bool $alreadyDispatched): self
    {
        if ($alreadyDispatched) {
            $this->queues
                ->skipUntil(fn (QueueData $queue): bool => $queue->dispatched === true && $queue->handled === false)
                ->first(function (QueueData $queue): bool {
                    $messageHandler = $this->getMessageHandlerByPriority($queue->priority);

                    $this->setFirstHandler($messageHandler);

                    $queue->markAsHandled();

                    return true;
                });
        }

        return $this;
    }

    /**
     * @return array<MessageHandler>|array
     */
    public function getSyncHandlers(): array
    {
        return $this->syncHandlers;
    }

    public function getAsyncHandler(): ?MessageHandler
    {
        return $this->asyncHandler;
    }

    public function getQueues(): array
    {
        return $this->queues
            ->values()
            ->map(fn (QueueData $queue) => $queue->jsonSerialize())->toArray();
    }

    /**
     * Set an unique async handler to dispatch.
     *
     * @throws RuntimeException
     */
    protected function setAsyncHandler(MessageHandler $messageHandler): void
    {
        if ($this->asyncHandler !== null) {
            throw new RuntimeException('Handler to dispatch async already set');
        }

        $this->asyncHandler = $messageHandler;
    }

    /**
     * Set the first handler to handle when it has been dispatched async.
     *
     * @throws RuntimeException
     */
    protected function setFirstHandler(MessageHandler $messageHandler): void
    {
        if ($this->syncHandlers !== []) {
            throw new RuntimeException('Story handlers should be empty when set first handler');
        }

        $this->syncHandlers[] = $messageHandler;
    }

    protected function getMessageHandlerByPriority(int $queuePriority): MessageHandler
    {
        return $this->messageHandlers->first(
            fn (MessageHandler $messageHandler): bool => $messageHandler->priority() === $queuePriority
        );
    }

    /**
     * Normalize the message queue.
     *
     * @return Collection<QueueData>
     */
    protected function normalizeQueues(array $queues): Collection
    {
        return ($queues === [] ? $this->newQueues() : $this->queuesFromArray($queues))
            ->values()
            ->sortBy(fn (QueueData $queue): int => $queue->priority);
    }

    protected function newQueues(): Collection
    {
        return $this->messageHandlers->map(function (MessageHandler $messageHandler): QueueData {
            return QueueData::newInstance(
                $messageHandler->priority(),
                $messageHandler->name(),
                $messageHandler->queue(),
            );
        });
    }

    protected function queuesFromArray(array $queues): Collection
    {
        return collect($queues)
            ->when(
                fn (Collection $queues): bool => $queues->count() !== count($this->messageHandlers),
                fn (Collection $queues) => throw new RuntimeException('Queues should be the same length as message handlers')
            )
            ->map(fn (array $queue): QueueData => QueueData::fromArray($queue));
    }

    /**
     * Assert that the queue is not already completed.
     *
     * Avoid some potential bugs when the queue is already completed
     * and could end up in a loop.
     *
     * @throws RuntimeException
     */
    private function assertNotAlreadyCompleted(): void
    {
        $this->queues
            ->filter(fn (QueueData $queue): bool => ! $queue->isCompleted())
            ->whenEmpty(fn () => throw new RuntimeException('Queue already completed'));
    }
}
