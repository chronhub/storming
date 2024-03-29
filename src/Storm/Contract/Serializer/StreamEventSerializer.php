<?php

declare(strict_types=1);

namespace Storm\Contract\Serializer;

use Storm\Contract\Message\DomainEvent;
use Storm\Serializer\Payload;

interface StreamEventSerializer
{
    public function serialize(DomainEvent $event): Payload;

    public function deserialize(object $object): DomainEvent;
}
