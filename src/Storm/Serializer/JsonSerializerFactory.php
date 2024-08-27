<?php

declare(strict_types=1);

namespace Storm\Serializer;

use Storm\Contract\Serializer\SymfonySerializer;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;

use function array_merge;
use function is_int;

class JsonSerializerFactory
{
    /** @var array<NormalizerInterface|DenormalizerInterface> */
    private array $normalizers = [];

    private array $context = [];

    private ?int $encodeOptions = null;

    private ?int $decodeOptions = null;

    public function withContext(array $context = []): self
    {
        $this->context = $context;

        return $this;
    }

    public function withEncodeOptions(int $encodeOptions): self
    {
        $this->encodeOptions = $encodeOptions;

        return $this;
    }

    public function withDecodeOptions(int $decodeOptions): self
    {
        $this->decodeOptions = $decodeOptions;

        return $this;
    }

    public function withNormalizer(NormalizerInterface|DenormalizerInterface ...$normalizers): self
    {
        $this->normalizers = array_merge($this->normalizers, $normalizers);

        return $this;
    }

    public function create(): SymfonySerializer
    {
        $normalizers = $this->getNormalizers();

        $serializer = new StormSerializer($normalizers, [$this->getJsonEncoder()]);

        foreach ($normalizers as $normalizer) {
            if ($normalizer instanceof SerializerAwareInterface) {
                $normalizer->setSerializer($serializer);
            }
        }

        return $serializer;
    }

    public function getJsonEncoder(): JsonEncoder
    {
        $encodeOptions = is_int($this->encodeOptions) ? [JsonEncode::OPTIONS => $this->encodeOptions] : [];
        $decodeOptions = is_int($this->decodeOptions) ? [JsonDecode::OPTIONS => $this->decodeOptions] : [];

        return new JsonEncoder(
            new JsonEncode($encodeOptions),
            new JsonDecode($decodeOptions),
            $this->context
        );
    }

    public function getNormalizers(): array
    {
        return $this->normalizers;
    }
}
