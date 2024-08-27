<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Serializer;

use stdClass;
use Storm\Contract\Message\EventHeader;
use Storm\Contract\Message\Header;
use Storm\Serializer\JsonSerializerFactory;
use Storm\Serializer\StreamEventNormalizer;
use Storm\Serializer\ToDomainEventSerializer;
use Storm\Tests\Stubs\Double\Message\SomeEvent;
use Symfony\Component\Uid\Uuid;

beforeEach(function () {
    $factory = new JsonSerializerFactory;
    $streamEventNormalizer = new StreamEventNormalizer;

    $factory->withNormalizer($streamEventNormalizer);

    $serializer = $factory->create();

    $this->streamingSerializer = new ToDomainEventSerializer($serializer);

    $event = SomeEvent::fromContent(['some' => 'value'])->withHeaders([
        EventHeader::AGGREGATE_ID => Uuid::v4(),
        EventHeader::AGGREGATE_TYPE => 'some-type',
        EventHeader::AGGREGATE_VERSION => 1,
        Header::EVENT_TYPE => SomeEvent::class,
    ]);

    $this->event = $event;
});

it('serialize domain event', function () {

    $payload = $this->streamingSerializer->serialize($this->event);

    $stdClass = new stdClass;
    $stdClass->header = $payload->header;
    $stdClass->content = $payload->content;

    $toDomainEvent = $this->streamingSerializer->deserialize($stdClass);

    expect($toDomainEvent)->toEqual($this->event);
});
