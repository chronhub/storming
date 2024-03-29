<?php

declare(strict_types=1);

namespace Storm\Serializer;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;

class PayloadNormalizer implements DenormalizerInterface, NormalizerInterface
{
    use SerializerAwareTrait;

    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        return [
            'headers' => $object->headers,
            'content' => $object->content,
            'seqNo' => $object->seqNo,
        ];
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): Payload
    {
        return new Payload($data['headers'], $data['content'], $data['seqNo'] ?? null);
    }

    public function supportsNormalization(mixed $data, ?string $format = null): bool
    {
        return $data instanceof Payload;
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null /* , array $context = [] */): bool
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
