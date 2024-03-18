<?php

declare(strict_types=1);

namespace Storm\Projector\Support\Token;

final class ConsumeToken extends AbstractTokenBucket
{
    public function consume(float $tokens = 1): bool
    {
        return $this->doConsume($tokens);
    }
}
