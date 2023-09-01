<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Serializer;

use DateTimeImmutable;
use DateTimeZone;
use Storm\Serializer\JsonSerializer;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

it('create new instance', function () {
    $jsonSerializer = new JsonSerializer();

    expect($jsonSerializer)
        ->toHaveProperties(['context', 'encodeOptions', 'decodeOptions'])
        ->toHaveScalarProperty('context', [])
        ->toHaveScalarProperty('encodeOptions', null)
        ->toHaveScalarProperty('decodeOptions', null);
});

describe('setter', function () {
    test('context', function (): void {
        $jsonSerializer = new JsonSerializer();

        expect($jsonSerializer)->toHaveScalarProperty('context', []);

        $jsonSerializer->withContext(['foo' => 'bar']);

        expect($jsonSerializer)->toHaveScalarProperty('context', ['foo' => 'bar']);
    });

    test('encode options', function () {
        $jsonSerializer = new JsonSerializer();

        expect($jsonSerializer)->toHaveScalarProperty('encodeOptions', null);

        $jsonSerializer->withEncodeOptions(JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);

        expect($jsonSerializer)->toHaveScalarProperty('encodeOptions', 1344);
    });

    test('decode options', function () {
        $jsonSerializer = new JsonSerializer();

        expect($jsonSerializer)->toHaveScalarProperty('decodeOptions', null);

        $jsonSerializer->withDecodeOptions(JSON_BIGINT_AS_STRING);

        expect($jsonSerializer)->toHaveScalarProperty('decodeOptions', 2);
    });
});

describe('json encoder instance', function (): void {
    it('create default instance', function () {
        $jsonSerializer = new JsonSerializer();

        $jsonEncoder = $jsonSerializer->getJsonEncoder();

        expect($jsonEncoder)
            ->toBeInstanceOf(JsonEncoder::class)
            ->toHaveScalarProperty('defaultContext', [JsonDecode::ASSOCIATIVE => true]);
    });

    it('create new instance', function () {
        $jsonSerializer = new JsonSerializer();

        $jsonEncoder = $jsonSerializer->getJsonEncoder();
        $aJsonEncoder = $jsonSerializer->getJsonEncoder();

        expect($jsonEncoder)->not()->toBe($aJsonEncoder)->toEqual($aJsonEncoder);
    });

    it('create instance with given context', function () {
        $jsonSerializer = new JsonSerializer();
        $jsonSerializer->withContext(['foo' => 'bar']);

        $jsonEncoder = $jsonSerializer->getJsonEncoder();

        expect($jsonEncoder)
            ->toBeInstanceOf(JsonEncoder::class)
            ->toHaveScalarProperty('defaultContext', [JsonDecode::ASSOCIATIVE => true, 'foo' => 'bar']);
    });

    it('create instance with given options', function () {
        //$jsonSerializer = new JsonSerializer();
    })->todo();
});

describe('symfony serializer instance', function (): void {
    it('return new instance', function () {
        $jsonSerializer = new JsonSerializer();

        $symfonySerializer = $jsonSerializer->create();

        expect($symfonySerializer)->not()->toBe($jsonSerializer->create())->toEqual($jsonSerializer->create());
    });

    it('serialize with normalizers', function (): void {
        $jsonSerializer = new JsonSerializer();

        $data = ['foo' => 'bar', 'int' => 42, 'datetime' => new DateTimeImmutable('2023-12-25', new DateTimeZone('UTC'))];

        $normalizer =
            new DateTimeNormalizer([
                DateTimeNormalizer::FORMAT_KEY => 'Y-m-d\TH:i:s.u',
                DateTimeNormalizer::TIMEZONE_KEY => 'UTC',
            ]);

        $symfonySerializer = $jsonSerializer->create($normalizer);

        expect($symfonySerializer->serialize($data, 'json'))
            ->toBe('{"foo":"bar","int":42,"datetime":"2023-12-25T00:00:00.000000"}');
    });
});
