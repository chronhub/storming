<?php

declare(strict_types=1);

namespace Storm\Contract\Serializer;

use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

interface JsonSerializer
{
    /**
     * Provide additional context to the serializer.
     *
     * @param  array<string, mixed> $context
     * @return $this
     */
    public function withContext(array $context = []): self;

    /**
     * Provide additional encoding options to the serializer.
     *
     * @param  positive-int $encodeOptions
     * @return $this
     */
    public function withEncodeOptions(int $encodeOptions): self;

    /**
     * Provide additional decoding options to the serializer.
     *
     * @param  positive-int $decodeOptions
     * @return $this
     */
    public function withDecodeOptions(int $decodeOptions): self;

    /**
     * Provide additional normalizer, denormalizer to the serializer.
     *
     * @return $this
     */
    public function withNormalizer(NormalizerInterface|DenormalizerInterface ...$normalizers): self;

    /**
     * Create a new serializer instance.
     */
    public function create(): SymfonySerializer;

    /**
     * Get the json encoder.
     */
    public function getJsonEncoder(): JsonEncoder;

    /**
     * Get the normalizers.
     *
     * @return NormalizerInterface[]|DenormalizerInterface[]
     */
    public function getNormalizers(): array;
}
