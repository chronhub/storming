<?php

declare(strict_types=1);

namespace Storm\Contract\Serializer;

use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;

interface JsonSerializer
{
    /**
     * @return $this
     */
    public function withContext(array $context = []): self;

    /**
     * @return $this
     */
    public function withEncodeOptions(int $encodeOptions): self;

    /**
     * @return $this
     */
    public function withDecodeOptions(int $decodeOptions): self;

    public function withNormalizer(NormalizerInterface|DenormalizerInterface ...$normalizers): self;

    public function create(): Serializer;

    public function getJsonEncoder(): JsonEncoder;

    /**
     * @return NormalizerInterface[]|DenormalizerInterface[]
     */
    public function getNormalizers(): array;
}
