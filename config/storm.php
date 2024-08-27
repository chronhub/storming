<?php

declare(strict_types=1);

return [

    /**
     * Message and event decorators.
     */
    'decorators' => [

        'message' => [
            \Storm\Message\Decorator\EventTime::class,
            \Storm\Message\Decorator\EventSymfonyId::class,
            \Storm\Message\Decorator\EventType::class,
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
