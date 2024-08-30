<?php

declare(strict_types=1);

namespace Storm\Story;

use Illuminate\Contracts\Container\Container;
use Illuminate\Pipeline\Pipeline;
use React\Promise\PromiseInterface;
use Storm\Contract\Message\MessageFactory;
use Storm\Contract\Serializer\MessageSerializer;
use Storm\Message\Message;
use Storm\Story\Build\MessageStoryResolver;
use Storm\Story\Exception\MessageNotHandled;
use Storm\Story\Middleware\DecorateMessage;

final readonly class DefaultStoryContext implements StoryContext
{
    private Pipeline $pipeline;

    public function __construct(
        private Container $container,
        private MessageFactory $messageFactory,
        private MessageSerializer $serializer,
        private MessageStoryResolver $messageResolver,
    ) {
        $this->pipeline = new Pipeline($this->container);
    }

    public function __invoke(array|object $payload, ?object $job = null): ?PromiseInterface
    {
        $message = $this->messageFactory->createMessageFrom($payload);

        return $this->handleMessage(
            $this->buildMessage($message),
            $this->messageResolver->getHandlers($message->type()),
            $job
        );
    }

    public function buildJob(Message $message, ?array $queue = null): object
    {
        $payload = $this->serializer->serializeMessage($message);

        return new MessageJob(
            $this->messageResolver->getContextId($message->type()),
            $payload->jsonSerialize(),
            $queue ?? $this->messageResolver->getQueue($message->type()),
        );
    }

    public function buildMessage(array|object $payload): Message
    {
        $message = $this->messageFactory->createMessageFrom($payload);

        $messageDecorator = $this->messageResolver->getMessageDecorator();

        return $this->pipeline
            ->send($message)
            ->through([new DecorateMessage(...$messageDecorator)])
            ->thenReturn();
    }

    private function handleMessage(Message $message, array $handlers, ?object $job = null): ?PromiseInterface
    {
        return $this->pipeline
            ->send(new Draft($message->event(), $handlers, $job))
            ->through($this->messageResolver->getMiddleware($message->type()))
            ->then(function (Draft $draft) {
                if (! $draft->isHandled()) {
                    throw MessageNotHandled::withMessage($draft->getMessage());
                }

                return $draft;
            })->getPromise();
    }
}
