<?php

declare(strict_types=1);

namespace Storm\Projector\Support\Event;

use DateTimeImmutable;
use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Message\Header;
use Symfony\Component\Uid\Uuid;

final class GenericEvent extends DecoratedEvent
{
    public static function fromEvent(DomainEvent $event): self
    {
        return new self($event);
    }

    public function id(): string|Uuid
    {
        return $this->event->header(Header::EVENT_ID);
    }

    public function time(): string|DateTimeImmutable
    {
        return $this->event->header(Header::EVENT_TIME);
    }
}
