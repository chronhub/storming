<?php

declare(strict_types=1);

namespace Storm\Story\Build;

use Arr;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use RuntimeException;
use Storm\Story\Attribute\AsCommandHandler;
use Storm\Story\Attribute\AsEventHandler;
use Storm\Story\Attribute\AsQueryHandler;
use Storm\Story\Exception\StoryViolation;
use Storm\Story\Support\MessageType;

use function array_filter;
use function array_map;
use function array_merge;
use function count;
use function get_class;
use function sprintf;
use function usort;

class AttributeMap
{
    /** @var array<string, array<MessageHandlerMetadata>>|array */
    protected array $metadata = [];

    protected array $attributes = [
        AsCommandHandler::class,
        AsEventHandler::class,
        AsQueryHandler::class,
    ];

    protected ?AttributeScanner $attributeScanner = null;

    public function __construct(array $directories)
    {
        if ($directories === []) {
            return;
        }

        $this->attributeScanner = new AttributeScanner($directories, $this->attributes);
    }

    public function build(): void
    {
        if ($this->attributeScanner === null) {
            return;
        }

        $classes = Arr::collapse($this->attributeScanner->scan());

        foreach ($classes as $class) {
            $this->buildMap($class);
        }
    }

    /**
     * @param  class-string                        $message
     * @return array<MessageHandlerMetadata>|array
     */
    public function get(string $message): array
    {
        return $this->metadata[$message] ?? [];
    }

    public function all(): array
    {
        return $this->metadata;
    }

    /**
     * @param class-string $class
     *
     * @throws ReflectionException
     */
    protected function buildMap(string $class): void
    {
        $reflection = new ReflectionClass($class);
        $attributes = $this->getAllAttributes($reflection);

        if ($attributes === []) {
            throw new StoryViolation("No attribute found for class $class");
        }

        foreach ($attributes as $attribute) {
            $this->processAttribute($attribute, $class);
        }
    }

    protected function getAllAttributes(ReflectionClass $reflection): array
    {
        $attributes = [
            ...$reflection->getAttributes(AsCommandHandler::class),
            ...$reflection->getAttributes(AsQueryHandler::class),
            ...$this->createEventAttributes($reflection),
        ];

        return array_filter($attributes);
    }

    protected function createEventAttributes(ReflectionClass $reflection): array
    {
        $classAttributes = $reflection->getAttributes(AsEventHandler::class);

        $methodAttributes = array_merge(...array_map(
            function (ReflectionMethod $method) {
                $attributes = $method->getAttributes(AsEventHandler::class);

                return array_map(
                    function (ReflectionAttribute $attribute) use ($method) {
                        $attribute = $attribute->newInstance();
                        $attribute->method = $method->getName();

                        return $attribute;
                    },
                    $attributes
                );
            },
            $reflection->getMethods()
        ));

        return array_map(
            function (ReflectionAttribute|AsEventHandler $attribute) {
                if ($attribute instanceof ReflectionAttribute) {
                    $attribute = $attribute->newInstance();
                }

                return $attribute;
            },
            [...$classAttributes, ...$methodAttributes]
        );
    }

    /**
     * @throws ReflectionException
     */
    protected function processAttribute(object $instance, string $messageHandler): void
    {
        if ($instance instanceof ReflectionAttribute) {
            $instance = $instance->newInstance();
        }

        if ($instance->handles === null) {
            $instance->handles = $this->getFirstParameterOfHandlerMethod($instance, $messageHandler);
        }

        $metadata = match ($instance::class) {
            AsCommandHandler::class => $this->createCommandInfo($instance, $messageHandler),
            AsQueryHandler::class => $this->createQueryInfo($instance, $messageHandler),
            AsEventHandler::class => $this->createEventInfo($instance, $messageHandler),
            default => throw new RuntimeException('Invalid attribute type '.get_class($instance)),
        };

        $this->metadata[$instance->handles][] = $metadata;

        if ($instance instanceof AsEventHandler && count($this->metadata[$instance->handles]) > 1) {
            $this->validateEventHandlers($instance->handles);
        }
    }

    protected function createCommandInfo(AsCommandHandler $attribute, string $messageHandler): MessageHandlerMetadata
    {
        return new MessageHandlerMetadata(
            type: MessageType::getType($attribute->handles),
            handler: $messageHandler,
            method: $attribute->method,
            queue: $attribute->queue,
            priority: 0,
            middleware: $attribute->middleware,
            contextId: $attribute->contextId,
        );
    }

    protected function createQueryInfo(AsQueryHandler $attribute, string $messageHandler): MessageHandlerMetadata
    {
        return new MessageHandlerMetadata(
            type: MessageType::getType($attribute->handles),
            handler: $messageHandler,
            method: $attribute->method,
            queue: [],
            priority: 0,
            middleware: $attribute->middleware,
            contextId: $attribute->contextId,
        );
    }

    protected function createEventInfo(AsEventHandler $attribute, string $messageHandler): MessageHandlerMetadata
    {
        return new MessageHandlerMetadata(
            type: MessageType::getType($attribute->handles),
            handler: $messageHandler,
            method: $attribute->method,
            queue: $attribute->queue,
            priority: $attribute->priority,
            middleware: $attribute->middleware,
            contextId: $attribute->contextId,
        );
    }

    /**
     * Validates that for a given event, only one handler with the highest priority
     * can set the queue, middleware, and contextId.
     *
     * @throws StoryViolation when many handlers are configured to set queue, middleware, or contextId
     */
    protected function validateEventHandlers(string $handles): void
    {
        /** @var array<MessageHandlerMetadata> $handlers */
        $handlers = $this->metadata[$handles];

        // Sort handlers by priority (descending)
        usort($handlers, fn ($a, $b) => $b->priority <=> $a->priority);

        // Get the handler with the highest priority
        $highestPriorityHandler = $handlers[0];

        // Check for other handlers that might set queue, middleware, or contextId
        foreach ($handlers as $handler) {
            if ($handler === $highestPriorityHandler) {
                continue;
            }

            if ($handler->queue !== [] || ! empty($handler->middleware) || ! empty($handler->contextId)) {
                throw new StoryViolation(sprintf(
                    'Event %s has multiple handlers trying to set queue, middleware, or contextId. '.
                    'Only the handler with the highest priority can set these. Conflict found in %s::%s.',
                    $handles,
                    $handler->handler,
                    $handler->method
                ));
            }
        }
    }

    /**
     * @throws ReflectionException
     * @throws StoryViolation      when no parameters are found
     * @throws StoryViolation      when the first parameter is not a named type
     */
    protected function getFirstParameterOfHandlerMethod(AsEventHandler|AsQueryHandler|AsCommandHandler $attribute, string $messageHandler): string
    {
        $reflection = new ReflectionMethod($messageHandler, $attribute->method);
        $parameters = $reflection->getParameters();

        if ($parameters === []) {
            throw new StoryViolation("No parameters found for handler method $messageHandler::$attribute->method");
        }

        $parameterType = $parameters[0]->getType();

        if (! $parameterType instanceof ReflectionNamedType || $parameterType->isBuiltin()) {
            throw new StoryViolation("Invalid parameter type for handler method $messageHandler::$attribute->method");
        }

        return $parameterType->getName();
    }
}
