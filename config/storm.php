<?php

declare(strict_types=1);

return [

    /**
     * Message and event decorators configuration.
     */
    'decorators' => [

        /**
         * Default chain message decorator for the CQRS pattern.
         *
         * Provides EventType, EventSymfonyId, and EventTime headers decorators.
         *
         * @see \Storm\Message\DefaultChainMessageDecorator
         */
        'message' => 'storm.message_decorator.chain',

        /**
         * Default chain event decorator for domain events produced by aggregates.
         *
         * By default, it just applies the same decorators as the message decorator above,
         * for the event type, event ID and event time headers.
         *
         * @see \Storm\Message\DefaultChainMessageDecorator
         */
        'event' => 'storm.event_decorator.chain',
    ],

    /**
     * Default serializer configuration.
     *
     * Fixed keys are used as they will be merged with other configurations.
     * Encode and decode options are not merged.
     *
     * @see \Storm\Serializer\SerializerFactory
     */
    'serializer' => [

        'json' => [
            'driver' => 'json',
            'normalizers' => [
                \Storm\Clock\PointInTimeNormalizer::class,
                \Storm\Serializer\PayloadNormalizer::class,
                \Symfony\Component\Serializer\Normalizer\JsonSerializableNormalizer::class,
                \Symfony\Component\Serializer\Normalizer\UidNormalizer::class,
            ],
            'context' => [],
        ],

        'messaging' => [
            'driver' => 'json',
            'normalizers' => [
                \Storm\Serializer\MessagingNormalizer::class,
            ],
            'context' => [],
        ],
    ],

    /**
     * List of directories to scan for attributes.
     *
     * @see \Storm\Story\Attribute\AsCommandHandler
     * @see \Storm\Story\Attribute\AsEventHandler
     * @see \Storm\Story\Attribute\AsQueryHandler
     * @see \Storm\Story\Attribute\Transactional
     */
    'scan' => [],
];
