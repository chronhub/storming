<?php

declare(strict_types=1);

namespace Storm\Chronicler\Database;

use Storm\Contract\Chronicler\StreamPersistence;
use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Serializer\SymfonySerializer;
use Storm\Stream\Stream;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

use function array_map;
use function iterator_to_array;

final readonly class StandardStreamPersistence implements StreamPersistence
{
    /**
     * The default strategy name for this persistence strategy.
     */
    final public const string STRATEGY_NAME = 'standard';

    public function __construct(private SymfonySerializer $serializer) {}

    public function normalize(Stream $stream): array
    {
        return array_map(
            fn (DomainEvent $event) => $this->normalizeEvent($event, $stream->byName),
            iterator_to_array($stream->events())
        );
    }

    public function getStrategyName(): string
    {
        return self::STRATEGY_NAME;
    }

    /**
     * @throws ExceptionInterface when an error occurs during normalization
     */
    private function normalizeEvent(DomainEvent $event, string $streamName): array
    {
        return $this->serializer->normalize($event, null, $this->getContext($streamName));
    }

    private function getContext(string $streamName): array
    {
        return ['strategy' => self::STRATEGY_NAME, 'streamName' => $streamName];
    }
}
