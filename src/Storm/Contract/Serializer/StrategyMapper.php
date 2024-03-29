<?php

declare(strict_types=1);

namespace Storm\Contract\Serializer;

interface StrategyMapper
{
    public function map(mixed $data, ?string $streamName): array;
}
