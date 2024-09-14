<?php

declare(strict_types=1);

namespace Storm\Story;

use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use React\Promise\PromiseInterface;
use Storm\Contract\Message\DomainQuery;
use Storm\Contract\Message\MessageFactory;
use Storm\Contract\Message\Messaging;
use Storm\Contract\Story\Story;
use Storm\Message\Message;
use Storm\Story\Build\MessageStoryResolver;

use function is_a;

final readonly class StoryDispatcher implements Story
{
    public function __construct(
        private MessageFactory $messageFactory,
        private MessageStoryResolver $storyResolver,
        private Dispatcher $dispatcher,
    ) {}

    public function relay(object|array $payload): ?PromiseInterface
    {
        $message = $this->messageFactory->createMessageFrom($payload);
        $context = $this->storyResolver->getContext($message->type());

        $message = $context->buildMessage($message);

        if ($this->shouldBeHandleSync($message->type())) {
            return $context($message->event());
        }

        $this->dispatchToQueue($context, $message);

        return null;
    }

    private function dispatchToQueue(StoryContext $context, Message $message): void
    {
        $job = $context->buildJob($message);

        $this->dispatcher->dispatchToQueue($job);
    }

    /**
     * Assess whether the message requires synchronous handling.
     */
    private function shouldBeHandleSync(string $message): bool
    {
        if (is_a($message, DomainQuery::class, true)) {
            return true;
        }

        if (! is_a($message, Messaging::class, true)) {
            return true;
        }

        return ! is_a($message, ShouldQueue::class, true);
    }
}
