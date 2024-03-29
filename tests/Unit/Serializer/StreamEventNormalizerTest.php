<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Serializer;

use Illuminate\Container\Container;
use Storm\Clock\PointInTime;
use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Message\EventHeader;
use Storm\Contract\Message\Header;
use Storm\Serializer\JsonSerializerFactory;
use Storm\Serializer\PayloadNormalizer;
use Storm\Serializer\StrategyMapperFactory;
use Storm\Serializer\StreamEventNormalizer;
use Storm\Tests\Stubs\Double\Message\SomeEvent;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\JsonSerializableNormalizer;
use Symfony\Component\Serializer\Normalizer\UidNormalizer;
use Symfony\Component\Uid\Uuid;

beforeEach(function () {
    $factory = new JsonSerializerFactory();

    $dateNormalizer = new DateTimeNormalizer([
        DateTimeNormalizer::FORMAT_KEY => 'Y-m-d\TH:i:s.u',
        DateTimeNormalizer::TIMEZONE_KEY => 'UTC',
    ]);

    $container = Container::getInstance();
    $container->instance(SystemClock::class, new PointInTime());
    $strategyFactory = new StrategyMapperFactory($container);
    $streamEventNormalizer = new StreamEventNormalizer($strategyFactory);

    $payloadNormalizer = new PayloadNormalizer();
    $uidNormalizer = new UidNormalizer();

    $factory->withNormalizer($uidNormalizer, $dateNormalizer, new JsonSerializableNormalizer(), $payloadNormalizer, $streamEventNormalizer);

    $this->serializer = $factory->create();
    $payloadNormalizer->setSerializer($this->serializer);
    $streamEventNormalizer->setSerializer($this->serializer);

    $event = SomeEvent::fromContent(['some' => 'value'])->withHeaders([
        EventHeader::AGGREGATE_ID => Uuid::v4(),
        EventHeader::AGGREGATE_TYPE => 'some-type',
        EventHeader::AGGREGATE_VERSION => 1,
        Header::EVENT_TYPE => SomeEvent::class,
    ]);

    $this->event = $event;
});

it('serialize stream event', function () {
    $serialized = $this->serializer->serialize($this->event, 'json', ['strategy' => 'standard', 'streamName' => 'some_stream']);

    $deserialized = $this->serializer->deserialize($serialized, SomeEvent::class, 'json');

    expect($deserialized)
        ->toBeInstanceOf(SomeEvent::class)
        ->toEqual($this->event);
});
