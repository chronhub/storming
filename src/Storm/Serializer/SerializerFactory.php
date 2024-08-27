<?php

declare(strict_types=1);

namespace Storm\Serializer;

use Illuminate\Contracts\Foundation\Application;
use Storm\Contract\Serializer\SymfonySerializer;
use Storm\Projector\Exception\InvalidArgumentException;

use function array_map;
use function array_merge;
use function is_int;

class SerializerFactory
{
    /**
     * The default storm serializer configuration.
     */
    protected array $config;

    public function __construct(private readonly Application $app)
    {
        $this->config = $this->app['config']->get('storm.serializer', []);

        if ($this->config == []) {
            throw new InvalidArgumentException('Storm serializer configuration not found.');
        }
    }

    public function create(array $config, bool $mergeWithDefaults = true): SymfonySerializer
    {
        $driver = $config['driver'] ?? null;

        return match ($driver) {
            'json' => $this->createJsonSerializer($config, $mergeWithDefaults),
            default => throw new InvalidArgumentException("Unsupported serializer driver: $driver"),
        };
    }

    private function createJsonSerializer(array $config, bool $mergeWithDefaults): SymfonySerializer
    {
        $config = $this->mergeConfig($config, 'json', $mergeWithDefaults);

        $factory = new JsonSerializerFactory;
        $factory
            ->withNormalizer(...($config['normalizers'] ?? []))
            ->withContext($config['context'] ?? []);

        if (is_int($config['encode_options'] ?? null)) {
            $factory->withEncodeOptions($config['encode_options']);
        }

        if (is_int($config['decode_options'] ?? null)) {
            $factory->withDecodeOptions($config['decode_options']);
        }

        return $factory->create();
    }

    private function mergeConfig(array $config, string $type, bool $mergeWithDefaults): array
    {
        if (! $mergeWithDefaults) {
            return $config;
        }

        $stormConfig = $this->config[$type] ?? [];

        if ($stormConfig == []) {
            throw new InvalidArgumentException("Storm serializer configuration for $type not found.");
        }

        $normalizers = array_merge($config['normalizers'] ?? [], $stormConfig['normalizers'] ?? []);
        $context = array_merge($config['context'] ?? [], $stormConfig['context'] ?? []);

        return [
            'driver' => $type,
            'normalizers' => array_map(fn (string $normalizer) => $this->app[$normalizer], $normalizers),
            'context' => $context,
            'encode_options' => $config['encode_options'] ?? null,
            'decode_options' => $config['decode_options'] ?? null,
        ];
    }
}
