<?php

declare(strict_types=1);

namespace Storm\Clock;

use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Throwable;

use function gettype;
use function is_string;
use function sprintf;

final class PointInTimeNormalizer implements DenormalizerInterface, NormalizerInterface
{
    public function normalize(mixed $object, ?string $format = null, array $context = []): string
    {
        if (! $object instanceof PointInTime) {
            throw new UnexpectedValueException(sprintf('Normalize object must be an instance of "%s".', PointInTime::class));
        }

        return $object->format();
    }

    public function supportsNormalization(mixed $data, ?string $format = null): bool
    {
        return $data instanceof PointInTime;
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): PointInTime
    {
        if (! is_string($data) || blank($data)) {
            throw new UnexpectedValueException(sprintf('Denormalize data must be a string and non empty, got "%s".', gettype($data)));
        }

        try {
            return PointInTime::fromString($data);
        } catch (Throwable $exception) {
            throw new NotNormalizableValueException($exception->getMessage(), (int) $exception->getCode(), $exception);
        }
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return is_string($data) && $type === PointInTime::class;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [PointInTime::class => true];
    }
}
