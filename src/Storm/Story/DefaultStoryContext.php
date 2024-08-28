<?php

declare(strict_types=1);

namespace Storm\Story;

use Illuminate\Contracts\Container\Container;
use Illuminate\Pipeline\Pipeline;
use React\Promise\PromiseInterface;
use Storm\Contract\Serializer\MessageSerializer;
use Storm\Message\Message;
use Storm\Story\Build\MessageStoryResolver;
use Storm\Story\Exception\MessageNotHandled;
use Storm\Story\Middleware\DecorateMessage;
use Storm\Story\Middleware\MakeMessage;

use function is_array;

final class DefaultStoryContext implements StoryContext
{
    /** @var array<string|object> */
    private array $onDispatch = [];

    private Pipeline $pipeline;

    public function __construct(
        private readonly Container $container,
        private readonly MessageSerializer $serializer,
        private readonly MessageStoryResolver $resolver,
    ) {
        $this->pipeline = new Pipeline($this->container);
        $this->setDecorator();
    }

    public function __invoke(array|object $payload, ?object $job = null): ?PromiseInterface
    {
        if (is_array($payload)) {
            $payload = $this->serializer->deserialize($payload);
        }

        return $this->handleMessage(
            $message = $this->buildMessage($payload),
            $this->resolver->getHandlers($message->type()),
            $job
        );
    }

    public function buildJob(Message $message, ?array $queue = null): object
    {
        $payload = $this->serializer->serializeMessage($message);

        return new MessageJob(
            $this->resolver->getContextId($message->type()),
            $payload->jsonSerialize(),
            $queue ?? $this->resolver->getQueue($message->type()),
        );
    }

    public function buildMessage(array|object $payload): Message
    {
        return $this->pipeline
            ->send($payload)
            ->through($this->onDispatch)
            ->thenReturn();
    }

    private function handleMessage(Message $message, array $handlers, ?object $job = null): ?PromiseInterface
    {
        return $this->pipeline
            ->send(new Draft($message->event(), $handlers, $job))
            ->through($this->resolver->getMiddleware($message->type()))
            ->then(function (Draft $draft) {
                if (! $draft->isHandled()) {
                    throw MessageNotHandled::withMessage($draft->getMessage());
                }

                return $draft;
            })->getPromise();
    }

    private function setDecorator(): void
    {
        $this->onDispatch = [
            MakeMessage::class,
            new DecorateMessage($this->container['storm.message_decorator.chain']),
        ];
    }
}
