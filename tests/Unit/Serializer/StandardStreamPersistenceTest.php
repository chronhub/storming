<?php

declare(strict_types=1);

namespace Storm\Serializer;

use Storm\Chronicler\Database\StandardStreamPersistence;
use Storm\Contract\Message\EventHeader;
use Storm\Contract\Message\Header;
use Storm\Stream\Stream;
use Storm\Stream\StreamName;
use Storm\Tests\Stubs\Double\Message\SomeEvent;
use Symfony\Component\Uid\Uuid;

beforeEach(function () {
    $factory = new JsonSerializerFactory;
    $streamEventNormalizer = new StreamEventNormalizer;
    $factory->withNormalizer($streamEventNormalizer);

    $this->serializer = $factory->create();

    $streamEventNormalizer->setSerializer($this->serializer);

    $this->streamPersistence = new StandardStreamPersistence($this->serializer);

    $event = SomeEvent::fromContent(['some' => 'value'])->withHeaders([
        EventHeader::AGGREGATE_ID => Uuid::v4(),
        EventHeader::AGGREGATE_TYPE => 'some-type',
        EventHeader::AGGREGATE_VERSION => 1,
        Header::EVENT_TYPE => SomeEvent::class,
    ]);

    $this->event = $event;
});

it('normalize stream event', function () {
    $stream = new Stream(new StreamName('some_stream'), [$this->event]);

    $normalized = $this->streamPersistence->normalize($stream);

    expect($normalized)->toBeArray()->and($normalized)->toHaveCount(1);

    $event = $normalized[0];

    expect($event)
        ->toHaveKeys(['stream_name', 'type', 'id', 'version', 'header', 'content'])
        ->toHaveKey('stream_name', 'some_stream');
});
