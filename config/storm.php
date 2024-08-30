<?php

declare(strict_types=1);

return [

    /**
     * Message and event decorators.
     */
    'decorators' => [

        /**
         * Default chain message decorator for the CQRS pattern.
         *
         * It provides EventType, EventSymfonyId and EventTime decorators.
         * It will be set on the AsHeader attribute when the property is not set,
         * or if the attribute is missing.
         * Also, the attribute allows you to add extra headers per message.
         *
         * Note that a [domain] event, which does not interact with the domain model,
         * should be considered as a message.
         *
         * @see \Storm\Message\DefaultChainMessageDecorator
         */
        'message' => [
            'default' => 'storm.message_decorator.chain',
        ],

        'event' => [
            \Storm\Message\Decorator\EventTime::class,
            \Storm\Message\Decorator\EventSymfonyId::class,
            \Storm\Message\Decorator\EventType::class,
        ],

    ],

    /**
     * Default serializer configuration.
     *
     * Fixed keys as they will be merged with other configurations.
     * Also, encode and decode options are not merged.
     *
     * @see \Storm\Serializer\\Storm\Serializer\SerializerFactory
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
            'encode_options' => null,
            'decode_options' => null,
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
     * List of directories to scan for message handlers
     */
    'scan' => [],
];
