<?php

declare(strict_types=1);

namespace Storm\Story;

use Illuminate\Contracts\Container\Container;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Arr;
use React\Promise\PromiseInterface;
use ReflectionClass;
use Storm\Contract\Message\MessageDecorator;
use Storm\Contract\Serializer\MessageSerializer;
use Storm\Message\Message;
use Storm\Story\Attribute\AsHeader;
use Storm\Story\Build\MessageStoryResolver;
use Storm\Story\Exception\MessageNotHandled;
use Storm\Story\Middleware\DecorateMessage;
use Storm\Story\Middleware\MakeMessage;
use Storm\Story\Support\MessageType;

use function array_map;
use function array_merge;
use function is_array;
use function is_string;

final class DefaultStoryContext implements StoryContext
{
    private Pipeline $pipeline;

    public function __construct(
        private readonly Container $container,
        private readonly MessageSerializer $serializer,
        private readonly MessageStoryResolver $resolver,
    ) {
        $this->pipeline = new Pipeline($this->container);
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
        $decorators = Arr::collapse($this->getMessageDecorators($payload));
        $decorators = array_map(fn (string $decorator): MessageDecorator => $this->container[$decorator], $decorators);

        // todo makeMessage should not be part of the pipeline, but as dependency MessageFactory
        $onDispatch = [MakeMessage::class, new DecorateMessage(...$decorators)];

        return $this->pipeline
            ->send($payload)
            ->through($onDispatch)
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

    private function getMessageDecorators(array|object $payload): array
    {
        $className = MessageType::getClassFrom($payload);

        $ref = new ReflectionClass($className);
        $attributes = $ref->getAttributes(AsHeader::class);

        $decorator = null;
        $extra = [];
        if ($attributes !== []) {
            /** @var AsHeader $instance */
            $instance = $attributes[0]->newInstance();

            if (is_string($instance->default)) {
                $decorator = $instance->default;
                $extra = $instance->decorators;
            }
        }

        if (is_string($decorator)) {
            return array_merge([$decorator], $extra);
        }

        return array_merge([config('storm.decorators.message.default')], $extra);
    }
}
