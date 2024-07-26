<?php

declare(strict_types=1);

namespace Storm\Message;

use InvalidArgumentException;
use RuntimeException;
use Storm\Contract\Message\Messaging;

trait MessageConstructorTrait
{
    private function validateEvent(object $event): void
    {
        if ($event instanceof self) {
            throw new InvalidArgumentException('Message event cannot be an instance of message');
        }
    }

    private function initializeNonMessagingEvent(object $event, array $headers): void
    {
        $this->event = $event;
        $this->headers = $headers;
    }

    private function initializeMessagingEvent(Messaging $event, array $headers): void
    {
        $expectedHeaders = $this->determineExpectedHeaders($event, $headers);

        $this->event = $event->withHeaders([]);
        $this->headers = $expectedHeaders;
    }

    private function determineExpectedHeaders(Messaging $event, array $headers): array
    {
        $eventHeaders = $event->headers();

        if ($eventHeaders === [] || $eventHeaders === $headers) {
            return $headers;
        }

        if ($headers === []) {
            return $eventHeaders;
        }

        throw new RuntimeException('Invalid headers consistency for event class '.$event::class);
    }
}
