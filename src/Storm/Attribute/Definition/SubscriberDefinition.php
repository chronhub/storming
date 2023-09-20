<?php

declare(strict_types=1);

namespace Storm\Attribute\Definition;

use InvalidArgumentException;
use Storm\Attribute\Definition;

use function class_exists;

/**
 * @template T of array{class: string, events: array{event: string, priority: int, method: non-empty-string}}
 */
final class SubscriberDefinition extends Definition
{
    /** @var array<T> */
    private array $events = [];

    public function __construct(protected string $className)
    {
        if (! class_exists($this->className)) {
            throw new InvalidArgumentException("Class $this->className does not exist");
        }
    }

    public function addEvent(string $eventName, int $priority, string $method): void
    {
        $this->events[] = [
            'event' => $eventName,
            'priority' => $priority,
            'method' => $method,
        ];
    }

    /**
     * @return array{class: string, events: array{T}, references: array}
     */
    public function jsonSerialize(): array
    {
        return [
            'class' => $this->className,
            'events' => $this->events,
            'references' => $this->references,
        ];
    }
}
