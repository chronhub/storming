<?php

declare(strict_types=1);

namespace Storm\Projector\Support\Token;

use Storm\Projector\Exception\NoMoreTokenBucketAvailable;

final class ConsumeOrFailToken extends AbstractTokenBucket
{
    public function consume(float $tokens = 1): bool
    {
        if ($this->doConsume($tokens)) {
            return true;
        }

        throw new NoMoreTokenBucketAvailable('No more token bucket available');
    }
}
