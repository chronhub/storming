<?php

declare(strict_types=1);

namespace Storm\Serializer;

use Storm\Contract\Serializer\JsonSerializer;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\JsonSerializableNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\UidNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerAwareInterface;

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
        $normalizers = $this->getNormalizers();

        $serializer = new Serializer($normalizers, [$this->getJsonEncoder()]);

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
        return array_merge($this->normalizers, $this->commons());
    }

    private function commons(): array
    {
        return [
            new UidNormalizer(),
            new DateTimeNormalizer([
                DateTimeNormalizer::FORMAT_KEY => 'Y-m-d\TH:i:s.u',
                DateTimeNormalizer::TIMEZONE_KEY => 'UTC',
            ]),
            new JsonSerializableNormalizer(),
            new PayloadNormalizer(),
        ];
    }
}
