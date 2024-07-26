<?php

declare(strict_types=1);

namespace Storm\Contract\Chronicler;

use Storm\Stream\Stream;

interface StreamPersistence
{
    /**
     * Normalize the stream.
     */
    public function normalize(Stream $stream): array;

    /**
     * Get the strategy name.
     */
    public function getStrategyName(): string;
}
