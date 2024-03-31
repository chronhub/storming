<?php

declare(strict_types=1);

namespace Storm\Serializer;

use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Storm\Contract\Serializer\StrategyMapper;

class StrategyMapperFactory
{
    public function __construct(protected Container $app)
    {
    }

    /**
     * @throws InvalidArgumentException when the strategy name is unknown
     */
    public function make(?string $strategyName): StrategyMapper
    {
        return match ($strategyName) {
            'standard' => $this->app[StandardStrategyMapper::class],
            default => throw new InvalidArgumentException("Unknown strategy: $strategyName"),
        };
    }
}
