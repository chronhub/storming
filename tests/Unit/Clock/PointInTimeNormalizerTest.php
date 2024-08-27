<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Clock;

use Carbon\Exceptions\InvalidFormatException;
use DateTimeImmutable;
use stdClass;
use Storm\Clock\PointInTime;
use Storm\Clock\PointInTimeNormalizer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Serializer;
use Throwable;

beforeEach(function () {
    $this->normalizer = new PointInTimeNormalizer();
    $this->serializer = new Serializer([$this->normalizer], [new JsonEncoder()]);
});

test('normalize point in time to string', function () {
    $pointInTime = PointInTime::now();
    expect($this->normalizer->supportsNormalization($pointInTime))->toBeTrue();

    $data = $this->serializer->normalize($pointInTime);

    expect($data)->toBeString()
        ->toMatch(PointInTime::DATE_TIME_PATTERN);
});

test('raise exception when normalize object is not an instance of PointInTime', function () {
    try {
        $this->normalizer->normalize(new stdClass());
    } catch (Throwable $e) {
        expect($e)->toBeInstanceOf(UnexpectedValueException::class)
            ->and($e->getMessage())->toContain('Normalize object must be an instance of')
            ->and($e->getCode())->toBe(0)
            ->and($e->getPrevious())->toBenull();
    }
});

test('does not support normalization', function (mixed $data) {
    expect($this->normalizer->supportsNormalization($data))->toBeFalse();
})->with([
    'null' => [null],
    'string' => ['2021-08-01T00:00:00.000000'],
    'array' => [[]],
    'object' => [new stdClass()],
    'datetime' => [new DateTimeImmutable()],
]);

test('support denormalization', function () {
    $data = '2021-08-01T00:00:00.000000';
    expect($this->normalizer->supportsDenormalization($data, PointInTime::class))->toBeTrue();
});

test('does not support denormalization', function (mixed $data, string $type) {
    expect($this->normalizer->supportsDenormalization($data, $type))->toBeFalse();
})->with([
    'null' => [null, PointInTime::class],
    'string' => ['2021-08-01T00:00:00.000000', stdClass::class],
    'array' => [[], PointInTime::class],
    'object' => [new stdClass(), PointInTime::class],
    'datetime' => [new DateTimeImmutable(), PointInTime::class],
]);

test('denormalize string to point in time', function () {
    $data = '2021-08-01T00:00:00.000000';
    $pointInTime = $this->serializer->denormalize($data, PointInTime::class);

    expect($pointInTime)->toBeInstanceOf(PointInTime::class)
        ->and($pointInTime->format())->toBe($data);
});

test('serialize and deserialize point in time', function () {
    $pointInTime = PointInTime::fromString('2021-08-01T00:00:00.000000');
    $data = $this->serializer->serialize($pointInTime, 'json');
    $pointInTime = $this->serializer->deserialize($data, PointInTime::class, 'json');

    expect($pointInTime)->toBeInstanceOf(PointInTime::class)
        ->and($pointInTime->format())->toBe('2021-08-01T00:00:00.000000');
});

test('raise exception with denormalize which accepts only string', function (mixed $data) {
    try {
        $this->normalizer->denormalize($data, PointInTime::class);
    } catch (Throwable $e) {
        expect($e)->toBeInstanceOf(UnexpectedValueException::class)
            ->and($e->getMessage())->toContain('Denormalize data must be a string and non empty')
            ->and($e->getCode())->toBe(0)
            ->and($e->getPrevious())->toBenull();
    }
})->with([
    'null' => [[null]],
    'empty string' => [''],
    'array' => [[]],
    'datetime' => [new DateTimeImmutable()],
    'true' => [true],
    'false' => [false],
    'int' => [1],
    'float' => [1.1],
]);

test('raise exception when denormalize fails', function (string $data) {
    try {
        $this->normalizer->denormalize($data, PointInTime::class);
    } catch (Throwable $e) {
        expect($e)->toBeInstanceOf(NotNormalizableValueException::class)
            ->and($e->getCode())->toBe(0)
            ->and($e->getPrevious())->toBeInstanceOf(InvalidFormatException::class);
    }
})->with([
    'random string' => ['random string'],
    'missing microseconds' => ['2021-08-01T00:00:00'],
    'missing timestamp' => ['2021-08-01 00:00:00 000000'],
    'partial ms 1' => ['2021-08-01T00:00:00.000000.0'],
    'partial ms 2' => ['2021-08-01T00:00:00.000000.00'],
    'partial ms 3' => ['2021-08-01T00:00:00.000000.000'],
    'partial ms 4' => ['2021-08-01T00:00:00.000000.0000'],
    'partial ms 5' => ['2021-08-01T00:00:00.000000.00000'],
]);
