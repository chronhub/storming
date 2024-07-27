<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Projector\InMemory\Factory;

use Storm\Contract\Serializer\SymfonySerializer;
use Storm\Serializer\JsonSerializerFactory;

final readonly class ProjectionSerializerFactory
{
    public function __construct(private JsonSerializerFactory $factory) {}

    public function make(): SymfonySerializer
    {
        return $this->factory
            ->withEncodeOptions(JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_FORCE_OBJECT)
            ->withDecodeOptions(JSON_OBJECT_AS_ARRAY | JSON_BIGINT_AS_STRING)
            ->create();
    }
}
