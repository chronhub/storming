<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Serializer;

use Illuminate\Container\Container;
use stdClass;
use Storm\Clock\PointInTime;
use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Message\EventHeader;
use Storm\Contract\Message\Header;
use Storm\Serializer\DomainEventSerializer;
use Storm\Serializer\JsonSerializerFactory;
use Storm\Serializer\PayloadNormalizer;
use Storm\Serializer\StrategyMapperFactory;
use Storm\Serializer\StreamEventNormalizer;
use Storm\Tests\Stubs\Double\Message\SomeEvent;
use Symfony\Component\Uid\Uuid;

beforeEach(function () {
    $factory = new JsonSerializerFactory();

    $payloadNormalizer = new PayloadNormalizer();

    $container = Container::getInstance();
    $container->instance(SystemClock::class, new PointInTime());
    $strategyFactory = new StrategyMapperFactory($container);
    $streamEventNormalizer = new StreamEventNormalizer($strategyFactory);

    $factory->withNormalizer($payloadNormalizer, $streamEventNormalizer);

    $this->serializer = $factory->create();

    $this->domainEventSerializer = new DomainEventSerializer($this->serializer);

    $event = SomeEvent::fromContent(['some' => 'value'])->withHeaders([
        EventHeader::AGGREGATE_ID => Uuid::v4(),
        EventHeader::AGGREGATE_TYPE => 'some-type',
        EventHeader::AGGREGATE_VERSION => 1,
        Header::EVENT_TYPE => SomeEvent::class,
    ]);

    $this->event = $event;
});

it('serialize domain event', function () {

    $payload = $this->domainEventSerializer->serialize($this->event);

    $stdClass = new stdClass();
    $stdClass->header = $payload->header;
    $stdClass->content = $payload->content;

    $toDomainEvent = $this->domainEventSerializer->deserialize($stdClass);

    dump($toDomainEvent);
});
