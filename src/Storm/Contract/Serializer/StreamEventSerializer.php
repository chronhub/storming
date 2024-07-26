<?php

declare(strict_types=1);

namespace Storm\Contract\Serializer;

use Storm\Contract\Message\DomainEvent;
use Storm\Serializer\Payload;

interface StreamEventSerializer
{
    /**
     * Serialize a domain event to a payload instance.
     */
    public function serialize(DomainEvent $event): Payload;

    /**
     * Deserialize data to a domain event instance.
     */
    public function deserialize(array|object $object): DomainEvent;
}
