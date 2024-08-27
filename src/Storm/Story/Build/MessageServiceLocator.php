<?php

declare(strict_types=1);

namespace Storm\Story\Build;

use Illuminate\Contracts\Container\Container;
use Storm\Story\Exception\MessageNotFound;
use Storm\Story\Exception\StoryException;
use Storm\Story\Exception\StoryViolation;
use Storm\Story\Support\MessageType;

use function sprintf;

readonly class MessageServiceLocator
{
    public function __construct(
        protected Container $container,
        protected AttributeMap $map,
    ) {}

    /**
     * Get the message handler(s) for the given message.
     *
     * @return array<callable>
     *
     * @throws MessageNotFound when no handler found for the message
     * @throws StoryViolation  when the story does not support the message handler
     */
    public function get(string $message): array
    {
        return collect($this->map->get($message))
            ->whenEmpty(function () use ($message) {
                throw new StoryException("No message handler found for the message $message.");
            })
            ->sortByDesc('priority')
            ->map(function (MessageHandlerMetadata $metadata) use ($message) {
                $this->assertSupportType($metadata, $message);

                return $this->makeCallableHandler($metadata);
            })->all();
    }

    /**
     * @return array<string, array<MessageHandlerMetadata>>
     */
    public function all(): array
    {
        return $this->map->all();
    }

    /**
     * Get the handler metadata for the given message.
     *
     * @return array<MessageHandlerMetadata>
     *
     * @throws MessageNotFound when no handler found for the message
     */
    public function getMetadata(string $message): array
    {
        $metadata = $this->map->get($message);

        if ($metadata === []) {
            throw MessageNotFound::forMessage($message);
        }

        return $metadata;
    }

    protected function makeCallableHandler(MessageHandlerMetadata $metadata): callable
    {
        $handler = $this->container[$metadata->handler];

        $method = $metadata->method;

        return $handler->$method(...);
    }

    /**
     * @throws StoryViolation when the story type does not match the handler type
     */
    protected function assertSupportType(MessageHandlerMetadata $metadata, string $message): void
    {
        $storyType = MessageType::getType($message);

        if ($metadata->type !== $storyType) {
            throw new StoryViolation(sprintf(
                'Story type mismatch, expected %s, got %s', $metadata->type, $storyType)
            );
        }
    }
}
