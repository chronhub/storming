<?php

declare(strict_types=1);

namespace Storm\Contract\Chronicler;

use Storm\Stream\Stream;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

interface StreamPersistence
{
    /**
     * Normalize the stream.
     *
     * @throws ExceptionInterface If an error occurs during normalization.
     */
    public function normalize(Stream $stream): array;

    /**
     * Get the strategy name.
     */
    public function getStrategyName(): string;
}
