<?php

declare(strict_types=1);

namespace Storm\Story\Support;

use Storm\Contract\Message\DomainCommand;
use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Message\Header;
use Storm\Message\Message;
use Storm\Story\Exception\StoryException;

use function class_exists;
use function get_class;
use function is_a;
use function is_array;
use function is_object;
use function is_string;

class MessageType
{
    final public const string COMMAND = 'command';

    final public const string EVENT = 'event';

    final public const string QUERY = 'query';

    /**
     * Determine the message type from the given payload.
     *
     * @return string{'command'|'event'|'query'}
     */
    public static function getType(string|array|object $payload): string
    {
        if (is_array($payload)) {
            return self::getTypeFromString(
                self::getEventTypeFromArray($payload)
            );
        }

        if (is_string($payload)) {
            return self::getTypeFromString($payload);
        }

        if ($payload instanceof Message) {
            $payload = $payload->type();

            return self::getTypeFromString($payload);
        }

        return self::getTypeFromString(get_class($payload));
    }

    /**
     * Determine the message type from the given message class.
     *
     * @param class-string $message
     * @return string{'command'|'event'|'query'}
     *
     * @throws StoryException when the message given is not a fully qualified class name
     */
    public static function getTypeFromString(string $message): string
    {
        self::assertValidClassName($message);

        return match (true) {
            is_a($message, DomainCommand::class, true) => self::COMMAND,
            is_a($message, DomainEvent::class, true) => self::EVENT,
            default => self::QUERY,
        };
    }

    /**
     * Get the fully qualified class name from the given payload.
     *
     * @return class-string
     *
     * @throws StoryException when the payload is not a fully qualified class name
     */
    public static function getClassFrom(string|array|object $payload): string
    {
        if ($payload instanceof Message) {
            return $payload->type();
        }

        if (is_object($payload)) {
            return get_class($payload);
        }

        if (is_array($payload)) {
            $payload = self::getEventTypeFromArray($payload);
        }

        self::assertValidClassName($payload);

        return $payload;
    }

    public static function isDomainCommand(string|array|object $payload): bool
    {
        return self::getType($payload) === self::COMMAND;
    }

    public static function isDomainEvent(string|array|object $payload): bool
    {
        return self::getType($payload) === self::EVENT;
    }

    public static function isDomainQuery(string|array|object $payload): bool
    {
        return self::getType($payload) === self::QUERY;
    }

    public static function getEventTypeFromArray(array $payload): string
    {
        // CheckMe: should depends on message factory, we just fit the current implementation
        //  see also message converter
        return $payload['header'][Header::EVENT_TYPE];
    }

    protected static function assertValidClassName(string $message): void
    {
        if (! class_exists($message)) {
            throw new StoryException('Invalid message class: '.$message);
        }
    }
}
