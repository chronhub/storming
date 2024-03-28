<?php

declare(strict_types=1);

namespace Storm\Serializer;

use Storm\Contract\Serializer\JsonSerializer;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;

use function array_merge;
use function is_int;

final class JsonSerializerFactory implements JsonSerializer
{
    /**
     * @var array NormalizerInterface|DenormalizerInterface[]
     */
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

    public function create(): Serializer
    {
        return new Serializer($this->normalizers, [$this->getJsonEncoder()]);
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
}
