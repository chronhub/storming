<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Serializer;

use Storm\Serializer\JsonSerializerFactory;
use Storm\Serializer\Payload;
use Storm\Serializer\PayloadNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\JsonSerializableNormalizer;

beforeEach(function () {
    $factory = new JsonSerializerFactory();
    $factory->withEncodeOptions(JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_FORCE_OBJECT);
    $factory->withDecodeOptions(JSON_OBJECT_AS_ARRAY | JSON_BIGINT_AS_STRING);

    $dateNormalizer = new DateTimeNormalizer([
        DateTimeNormalizer::FORMAT_KEY => 'Y-m-d\TH:i:s.u',
        DateTimeNormalizer::TIMEZONE_KEY => 'UTC',
    ]);

    $payloadNormalizer = new PayloadNormalizer();

    $factory->withNormalizer($dateNormalizer, new JsonSerializableNormalizer(), $payloadNormalizer);

    $this->serializer = $factory->create();
    $payloadNormalizer->setSerializer($this->serializer);
});

it('test serializer', function () {
    $payload = new Payload(['some' => 'header'], ['some' => 'value'], 1);

    $serialized = $this->serializer->serialize($payload, 'json');

    expect($serialized)->toBe('{"headers":{"some":"header"},"content":{"some":"value"},"seqNo":1}');

    $deserialized = $this->serializer->deserialize($serialized, Payload::class, 'json');

    expect($deserialized)->toEqual($payload);
});

it('assert serializer as string headers', function () {
    $payload = new Payload('{"headers":{"some":"header"}', ['some' => 'value'], 1);

    $serialized = $this->serializer->serialize($payload, 'json');

    $deserialized = $this->serializer->deserialize($serialized, Payload::class, 'json');

    expect($deserialized)->toEqual($payload);
});
