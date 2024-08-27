<?php

declare(strict_types=1);

namespace Storm\Contract\Serializer;

use Storm\Contract\Message\DomainEvent;
use Storm\Serializer\Payload;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

interface StreamEventSerializer
{
    /**
     * Serialize a domain event to a payload instance.
     *
     * @throw ExceptionInterface when an error occurs during normalization
     */
    public function serialize(DomainEvent $event): Payload;

    /**
     * Deserialize data.
     *
     * @throws ExceptionInterface when an error occurs during denormalization
     */
    public function deserialize(array|object $object): DomainEvent|array;
}
