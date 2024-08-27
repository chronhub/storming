<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Serializer;

use Storm\Clock\ClockFactory;
use Storm\Clock\PointInTime;
use Storm\Clock\PointInTimeNormalizer;
use Storm\Serializer\JsonSerializerFactory;
use Storm\Serializer\PayloadNormalizer;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\JsonSerializableNormalizer;
use Symfony\Component\Serializer\Normalizer\UidNormalizer;

use function array_map;
use function count;
use function get_class;

beforeEach(function () {
    $this->jsonSerializer = new JsonSerializerFactory();
});

afterEach(function () {
    $this->jsonSerializer = null;
});

test('default instance', function () {
    expect($this->jsonSerializer)
        ->toHaveProperties(['context', 'encodeOptions', 'decodeOptions', 'normalizers'])
        ->toHaveScalarProperty('context', [])
        ->toHaveScalarProperty('encodeOptions', null)
        ->toHaveScalarProperty('decodeOptions', null);

    $normalizers = array_map(
        fn ($normalizer) => get_class($normalizer),
        $this->jsonSerializer->getNormalizers()
    );

    $expected = [
        PayloadNormalizer::class,
        JsonSerializableNormalizer::class,
        PointInTimeNormalizer::class,
        UidNormalizer::class,
    ];

    expect(count($normalizers))->toBe(count($expected));

    foreach ($expected as $normalizer) {
        expect($normalizers)->toContain($normalizer);
    }
});

test('assert point in time normalizer', function () {
    $normalizers = $this->jsonSerializer->getNormalizers();

    $dateTimeNormalizer = null;
    foreach ($normalizers as $normalizer) {
        if (! $normalizer instanceof PointInTimeNormalizer) {
            continue;
        }

        $dateTimeNormalizer = $normalizer;
    }

    $now = ClockFactory::create()->now();
    $timeAsString = $dateTimeNormalizer->normalize($now, PointInTime::class);

    expect($timeAsString)->toMatch(PointInTime::DATE_TIME_PATTERN);
});

describe('setter', function () {
    test('context', function (): void {
        expect($this->jsonSerializer)->toHaveScalarProperty('context', []);

        $this->jsonSerializer->withContext(['foo' => 'bar']);

        expect($this->jsonSerializer)->toHaveScalarProperty('context', ['foo' => 'bar']);
    });

    test('encode options', function () {
        expect($this->jsonSerializer)->toHaveScalarProperty('encodeOptions', null);

        $this->jsonSerializer->withEncodeOptions(JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);

        expect($this->jsonSerializer)->toHaveScalarProperty('encodeOptions', 1344);
    });

    test('decode options', function () {
        expect($this->jsonSerializer)->toHaveScalarProperty('decodeOptions', null);

        $this->jsonSerializer->withDecodeOptions(JSON_BIGINT_AS_STRING);

        expect($this->jsonSerializer)->toHaveScalarProperty('decodeOptions', 2);
    });
});

describe('json encoder instance', function (): void {
    it('create default instance', function () {
        $jsonEncoder = $this->jsonSerializer->getJsonEncoder();

        expect($jsonEncoder)
            ->toBeInstanceOf(JsonEncoder::class)
            ->toHaveScalarProperty('defaultContext', [JsonDecode::ASSOCIATIVE => true]);
    });

    it('create new instance', function () {
        $jsonEncoder = $this->jsonSerializer->getJsonEncoder();
        $aJsonEncoder = $this->jsonSerializer->getJsonEncoder();

        expect($jsonEncoder)->not()->toBe($aJsonEncoder)->toEqual($aJsonEncoder);
    });

    it('create instance with given context', function () {
        $this->jsonSerializer->withContext(['foo' => 'bar']);

        $jsonEncoder = $this->jsonSerializer->getJsonEncoder();

        expect($jsonEncoder)
            ->toBeInstanceOf(JsonEncoder::class)
            ->toHaveScalarProperty('defaultContext', [JsonDecode::ASSOCIATIVE => true, 'foo' => 'bar']);
    });
});

describe('symfony serializer instance', function (): void {
    it('return new instance', function () {
        $symfonySerializer = $this->jsonSerializer->create();

        expect($symfonySerializer)->not()->toBe($this->jsonSerializer->create())->toEqual($this->jsonSerializer->create());
    });

    it('serialize with normalizers', function (): void {
        $data = ['foo' => 'bar', 'int' => 42, 'datetime' => '2023-12-25T00:00:00.000000'];

        $symfonySerializer = $this->jsonSerializer->create();

        expect($symfonySerializer->serialize($data, 'json'))
            ->toBe('{"foo":"bar","int":42,"datetime":"2023-12-25T00:00:00.000000"}');
    });

    it('serialize with normalizers 2', function (): void {
        $data = ['foo' => 'bar', 'int' => 42, 'datetime' => PointInTime::fromString('2023-12-25T00:00:00.000000')];

        $symfonySerializer = $this->jsonSerializer->create();

        expect($symfonySerializer->serialize($data, 'json'))
            ->toBe('{"foo":"bar","int":42,"datetime":"2023-12-25T00:00:00.000000"}');
    });

    it('decode', function (): void {
        $data = ['foo' => 'bar', 'int' => 42, 'datetime' => '2023-12-25T00:00:00.000000'];

        $symfonySerializer = $this->jsonSerializer->create();

        $serialized = $symfonySerializer->serialize($data, 'json');

        expect($symfonySerializer->decode($serialized, 'json'))
            ->toEqual([
                'foo' => 'bar',
                'int' => 42,
                'datetime' => '2023-12-25T00:00:00.000000',
            ]);
    });

    it('decode 2', function (): void {
        $data = ['foo' => 'bar', 'int' => 42, 'datetime' => PointInTime::fromString('2023-12-25T00:00:00.000000')];

        $symfonySerializer = $this->jsonSerializer->create();

        $serialized = $symfonySerializer->serialize($data, 'json');

        expect($symfonySerializer->decode($serialized, 'json'))
            ->toEqual([
                'foo' => 'bar',
                'int' => 42,
                'datetime' => '2023-12-25T00:00:00.000000',
            ]);
    });
});
