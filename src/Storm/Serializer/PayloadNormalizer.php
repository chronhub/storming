<?php

declare(strict_types=1);

namespace Storm\Serializer;

use InvalidArgumentException;
use stdClass;
use Storm\Contract\Message\EventHeader;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;

use function is_array;
use function is_string;
use function json_decode;
use function json_encode;

class PayloadNormalizer implements DenormalizerInterface, NormalizerInterface, SerializerAwareInterface
{
    use SerializerAwareTrait;

    public function serializer(): Serializer
    {
        if (! $this->serializer instanceof Serializer) {
            throw new InvalidArgumentException('Serializer is not set');
        }

        return $this->serializer;
    }

    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        return [
            'header' => $object->headers,
            'content' => $object->content,
            'seqNo' => $object->seqNo,
        ];
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): Payload
    {
        $data = $this->convertData($data);

        [$header, $content, $seqNo] = $this->decodePartsIfNeeded($data);

        return new Payload($header, $content, $seqNo);
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

    private function convertData(mixed $data): array
    {
        if (! is_array($data) && ! $data instanceof stdClass) {
            throw new InvalidArgumentException('Payload data is invalid for denormalization, expected array or stdClass');
        }

        if ($data instanceof stdClass) {
            $data = json_decode(json_encode($data), true);
        }

        return $data;
    }

    /**
     * @return array{0: array, 1: array, 2: int|null}
     */
    private function decodePartsIfNeeded(array $payload): array
    {
        $header = $payload['header'];
        $content = $payload['content'];
        $seqNo = $payload['seqNo'] ?? null;

        if (is_string($header)) {
            $header = $this->serializer()->decode($header, 'json');
        }

        if (is_string($content)) {
            $content = $this->serializer()->decode($content, 'json');
        }

        if (! isset($header[EventHeader::INTERNAL_POSITION]) && $seqNo !== null) {
            $header[EventHeader::INTERNAL_POSITION] = $seqNo;
        }

        return [$header, $content, $seqNo];
    }
}
