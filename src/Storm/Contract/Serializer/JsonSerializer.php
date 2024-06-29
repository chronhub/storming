<?php

declare(strict_types=1);

namespace Storm\Contract\Serializer;

use Storm\Serializer\PayloadNormalizer;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

interface JsonSerializer
{
    /**
     * Add a context to the serializer.
     *
     * @param  array<string, mixed> $context
     * @return $this
     */
    public function withContext(array $context = []): self;

    /**
     * Add encoding options to the serializer.
     *
     * @param  positive-int $encodeOptions
     * @return $this
     */
    public function withEncodeOptions(int $encodeOptions): self;

    /**
     * Add decoding options to the serializer.
     *
     * @param  positive-int $decodeOptions
     * @return $this
     */
    public function withDecodeOptions(int $decodeOptions): self;

    /**
     * Add normalizers to the serializer.
     *
     * @return $this
     */
    public function withNormalizer(NormalizerInterface|DenormalizerInterface ...$normalizers): self;

    /**
     * Create a new serializer instance.
     */
    public function create(): SerializerInterface&EncoderInterface&DecoderInterface;

    /**
     * Get the json encoder.
     */
    public function getJsonEncoder(): JsonEncoder;

    /**
     * Get the normalizers.
     *
     * Default implementation returns at least:
     *
     * @see DateTimeNormalizer
     * @see JsonSerializableNormalizer
     * @see UidNormalizer
     * @see PayloadNormalizer
     *
     * @return NormalizerInterface[]|DenormalizerInterface[]
     */
    public function getNormalizers(): array;
}
