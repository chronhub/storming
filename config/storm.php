<?php

declare(strict_types=1);

return [
    'clock' => [
        'concrete' => \Storm\Clock\Clock::class,
        'normalizer' => \Storm\Clock\PointInTimeNormalizer::class,
    ],

    'serializer' => [
        'factory' => \Storm\Serializer\JsonSerializerFactory::class,
        'normalizers' => [
            \Storm\Serializer\PayloadNormalizer::class,
            \Symfony\Component\Serializer\Normalizer\UidNormalizer::class,
            \Storm\Clock\PointInTimeNormalizer::class,
            \Symfony\Component\Serializer\Normalizer\JsonSerializableNormalizer::class,
        ],
        'messaging' => [
            [
                'concrete' => \Storm\Serializer\MessagingSerializer::class,
                'options' => 0,
                'normalizers' => [
                    \Storm\Serializer\MessagingNormalizer::class,
                ],
            ],
        ],
        'streaming' => [
            [
                'concrete' => \Storm\Serializer\StreamingSerializer::class,
                'options' => 0,
                'normalizers' => [
                    \Storm\Serializer\StreamEventNormalizer::class,
                ],
            ],
            'projection' => [
                'concrete' => \Storm\Serializer\StreamingSerializer::class,
                'options' => [
                    'encode' => JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_FORCE_OBJECT,
                    'decode' => JSON_OBJECT_AS_ARRAY | JSON_BIGINT_AS_STRING,
                ],
                'normalizers' => [],
            ],

        ],
    ],
];
