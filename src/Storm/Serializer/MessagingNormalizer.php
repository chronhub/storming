<?php

declare(strict_types=1);

namespace Storm\Serializer;

use InvalidArgumentException;
use Storm\Contract\Message\Header;
use Storm\Contract\Message\Messaging;
use Storm\Contract\Serializer\SymfonySerializer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;

use function is_a;

final class MessagingNormalizer implements DenormalizerInterface, NormalizerInterface, SerializerAwareInterface
{
    use SerializerAwareTrait;

    protected function serializer(): SymfonySerializer
    {
        if (! $this->serializer instanceof SymfonySerializer) {
            throw new InvalidArgumentException('Serializer is not set or invalid');
        }

        return $this->serializer;
    }

    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        $payload = new Payload($object->headers(), $object->toContent());

        return $this->serializer()->normalize($payload, 'json');
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): Messaging
    {
        $eventType = $data['header'][Header::EVENT_TYPE] ?? null;

        /** @var Messaging|null $eventType */
        if ($eventType === null) {
            throw new InvalidArgumentException('Missing event type header string to denormalize Messaging');
        }

        return $eventType::fromContent($data['content'])->withHeaders($data['header']);
    }

    public function supportsNormalization(mixed $data, ?string $format = null): bool
    {
        return $data instanceof Messaging;
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null): bool
    {
        return is_a($type, Messaging::class, true);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            'object' => false,
            '*' => false,
            Messaging::class => true,
        ];
    }
}
