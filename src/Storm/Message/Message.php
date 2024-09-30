<?php

declare(strict_types=1);

namespace Storm\Message;

use InvalidArgumentException;
use RuntimeException;
use Storm\Contract\Message\Header;
use Storm\Contract\Message\Messaging;

final class Message
{
    use MessageConstructorTrait;
    use ReadHeaders;

    protected object $event;

    /**
     * @throws InvalidArgumentException when event is an instance of Message
     * @throws RuntimeException         when headers are inconsistent with event headers
     */
    public function __construct(object $event, array $headers = [])
    {
        $this->validateEvent($event);

        $event instanceof Messaging
           ? $this->initializeMessagingEvent($event, $headers)
           : $this->initializeNonMessagingEvent($event, $headers);
    }

    /**
     * @return class-string
     */
    public function type(): string
    {
        return $this->headers[Header::EVENT_TYPE] ?? $this->event::class;
    }

    public function event(): object
    {
        if ($this->event instanceof Messaging) {
            return clone $this->event->withHeaders($this->headers);
        }

        return clone $this->event;
    }

    public function withHeader(string $key, mixed $value): self
    {
        $message = clone $this;

        $message->headers[$key] = $value;

        return $message;
    }

    public function withHeaders(array $headers): self
    {
        $message = clone $this;

        $message->headers = $headers;

        return $message;
    }

    public function isMessaging(): bool
    {
        return $this->event instanceof Messaging;
    }
}
