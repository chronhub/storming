<?php

declare(strict_types=1);

namespace Storm\Serializer;

use InvalidArgumentException;
use RuntimeException;
use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Message\EventHeader;
use Storm\Contract\Message\Header;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;

use function is_a;

final class StreamEventNormalizer implements DenormalizerInterface, NormalizerInterface, SerializerAwareInterface
{
    use PayloadDecoderTrait;
    use SerializerAwareTrait;

    protected function serializer(): Serializer
    {
        if (! $this->serializer instanceof Serializer) {
            throw new RuntimeException('Serializer is not set or invalid');
        }

        return $this->serializer;
    }

    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        $strategy = $context['strategy'] ?? null;

        if ($strategy !== 'standard') {
            throw new InvalidArgumentException('Only standard strategy is supported');
        }

        $streamName = $context['streamName'] ?? null;

        if ($streamName === null) {
            throw new InvalidArgumentException('Stream name is required to normalize StreamEvent');
        }

        $payload = new Payload($object->headers(), $object->toContent());

        return $this->map($this->serializer()->normalize($payload, 'json'), $streamName);
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): DomainEvent
    {
        // todo add context to return raw data instead of domain event

        [$header, $content] = $this->decodePartsIfNeeded($data);

        $eventType = $header[Header::EVENT_TYPE] ?? null;

        /** @var DomainEvent|null $eventType */
        if ($eventType === null) {
            throw new InvalidArgumentException('Missing event type header string to deserialize payload');
        }

        return $eventType::fromContent($content)->withHeaders($header);
    }

    public function supportsNormalization(mixed $data, ?string $format = null): bool
    {
        return $data instanceof DomainEvent;
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null /* , array $context = [] */): bool
    {
        return is_a($type, DomainEvent::class, true);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            'object' => false,
            '*' => false,
            DomainEvent::class => true,
        ];
    }

    protected function map(array $data, string $streamName): array
    {
        return [
            'stream_name' => $streamName,
            'type' => $data['header'][EventHeader::AGGREGATE_TYPE],
            'id' => $data['header'][EventHeader::AGGREGATE_ID],
            'version' => $data['header'][EventHeader::AGGREGATE_VERSION],
            'header' => $this->serializer()->serialize($data['header'], 'json'),
            'content' => $this->serializer()->serialize($data['content'], 'json'),
            //'created_at' => $this->clock->generate(),
        ];
    }
}
