<?php

declare(strict_types=1);

namespace Storm\Story\Build;

use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use Storm\Story\Exception\MessageNotFound;
use Storm\Story\Exception\StoryException;
use Storm\Story\Middleware\HandleCommand;
use Storm\Story\Middleware\HandleEvent;
use Storm\Story\Middleware\HandleQuery;
use Storm\Story\StoryContext;
use Storm\Story\StoryServiceProvider;
use Storm\Story\Support\MessageType;

use function is_array;
use function is_callable;
use function is_string;
use function iterator_to_array;

readonly class MessageStoryResolver
{
    public function __construct(
        protected Container $container,
        protected MessageServiceLocator $locator,
    ) {}

    /**
     * Get the story context for the given message.
     *
     * @param class-string $message
     */
    public function getContext(string $message): StoryContext
    {
        $contextId = $this->getContextId($message);

        return $this->container[$contextId];
    }

    public function getContextId(string $message): string
    {
        $data = $this->locator->getMetadata($message);
        $contextId = $this->sortMetadataByDescendingPriority($data)[0]->contextId;

        return $contextId ?? StoryServiceProvider::DEFAULT_STORY_CONTEXT_ID; //fixMe in config ?
    }

    /**
     * Get the message handlers for the given message.
     *
     * @param  class-string          $message
     * @return array<callable>|array
     *
     * @throws MessageNotFound when the message not found
     */
    public function getHandlers(string $message): array
    {
        // Message handlers already sorted by descending priority
        // in the message service locator
        return $this->locator->get($message);
    }

    /**
     * Get the queue options when configured for the given message.
     *
     * @param class-string $message
     */
    public function getQueue(string $message): array
    {
        $data = $this->locator->getMetadata($message);
        $queue = $this->sortMetadataByDescendingPriority($data)[0]->queue;

        if (is_string($queue)) {
            $queue = $this->container[$queue];

            return iterator_to_array(
                $this->resolveIterableInstance($queue, $message, 'options')
            );
        }

        return $queue;
    }

    /**
     * Get the middleware for the given message.
     */
    public function getMiddleware(string $message): array
    {
        $data = $this->locator->getMetadata($message);
        $middleware = $this->sortMetadataByDescendingPriority($data)[0]->middleware;

        if (is_array($middleware)) {
            return $middleware !== [] ? $middleware : $this->buildDefaultMiddleware($message);
        }

        return iterator_to_array($this->resolveMiddleware($middleware, $message));
    }

    protected function buildDefaultMiddleware(string $message): array
    {
        return match (true) {
            MessageType::isDomainCommand($message) => [HandleCommand::class],
            MessageType::isDomainEvent($message) => [HandleEvent::class],
            default => [HandleQuery::class],
        };
    }

    protected function resolveMiddleware(string $middleware, string $message): iterable
    {
        return $this->resolveIterableInstance(
            $this->container[$middleware], $message, 'middleware'
        );
    }

    protected function resolveIterableInstance(object $instance, string $message, string $property): iterable
    {
        if ($instance instanceof JsonSerializable) {
            return $instance->jsonSerialize();
        } elseif ($instance instanceof Arrayable) {
            return $instance->toArray();
        } elseif (is_callable($instance)) {
            return $instance();
        }

        throw new StoryException("Invalid string $property for message $message, must return an iterable value");
    }

    /**
     * Sort metadata by descending priority.
     *
     * @param  array<MessageHandlerMetadata> $metadata
     * @return array<MessageHandlerMetadata>
     */
    protected function sortMetadataByDescendingPriority(array $metadata): array
    {
        return collect($metadata)->sortByDesc('priority')->all();
    }
}
