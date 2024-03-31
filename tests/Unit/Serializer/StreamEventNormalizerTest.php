<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Serializer;

use InvalidArgumentException;
use Storm\Contract\Message\EventHeader;
use Storm\Contract\Message\Header;
use Storm\Serializer\JsonSerializerFactory;
use Storm\Serializer\StreamEventNormalizer;
use Storm\Tests\Stubs\Double\Message\SomeEvent;
use Symfony\Component\Uid\Uuid;

beforeEach(function () {
    $factory = new JsonSerializerFactory();
    $streamEventNormalizer = new StreamEventNormalizer();

    $factory->withNormalizer($streamEventNormalizer);

    $this->serializer = $factory->create();
    $streamEventNormalizer->setSerializer($this->serializer);

    $event = SomeEvent::fromContent(['some' => 'value'])->withHeaders([
        EventHeader::AGGREGATE_ID => Uuid::v4(),
        EventHeader::AGGREGATE_TYPE => 'some-type',
        EventHeader::AGGREGATE_VERSION => 1,
        Header::EVENT_TYPE => SomeEvent::class,
    ]);

    $this->event = $event;
});

it('can not deserialize stream event as header and content are still serialized', function () {
    $serialized = $this->serializer->serialize($this->event, 'json', ['strategy' => 'standard', 'streamName' => 'some_stream']);

    try {
        $this->serializer->deserialize($serialized, SomeEvent::class, 'json');
    } catch (InvalidArgumentException $e) {
        expect($e->getMessage())->toBe('Missing event type header string to deserialize payload');
    }
});
