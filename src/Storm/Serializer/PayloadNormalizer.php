<?php

declare(strict_types=1);

namespace Storm\Serializer;

use InvalidArgumentException;
use Storm\Contract\Serializer\SymfonySerializer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;

final class PayloadNormalizer implements DenormalizerInterface, NormalizerInterface, SerializerAwareInterface
{
    use PayloadDecoderTrait;
    use SerializerAwareTrait;

    public function serializer(): SymfonySerializer
    {
        if (! $this->serializer instanceof SymfonySerializer) {
            throw new InvalidArgumentException('Serializer is not set or invalid');
        }

        return $this->serializer;
    }

    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        return $object->jsonSerialize();
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): Payload
    {
        [$header, $content, $position] = $this->decodePartsIfNeeded($data);

        return new Payload($header, $content, $position);
    }

    public function supportsNormalization(mixed $data, ?string $format = null): bool
    {
        return $data instanceof Payload;
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null): bool
    {
        return $type === Payload::class;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            'object' => false,
            '*' => false,
            Payload::class => true,
        ];
    }
}
