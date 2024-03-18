<?php

declare(strict_types=1);

namespace Storm\Projector\Support\Event;

use DateTimeImmutable;
use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Message\EventHeader;

abstract class DecoratedEvent
{
    protected function __construct(public readonly DomainEvent $event)
    {
    }

    abstract public static function fromEvent(DomainEvent $event): self;

    abstract public function id(): mixed;

    abstract public function time(): string|DateTimeImmutable;

    public function content(): array
    {
        return $this->event->toContent();
    }

    public function internalPosition(): int
    {
        return $this->event->header(EventHeader::INTERNAL_POSITION);
    }
}
