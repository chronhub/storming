<?php

declare(strict_types=1);

namespace Storm\Projector\Support\Token;

use Illuminate\Support\Sleep;
use Storm\Projector\Exception\InvalidArgumentException;

use function max;
use function microtime;
use function min;

final class ConsumeWithSleepToken extends AbstractTokenBucket
{
    public function consume(float $tokens = 1): bool
    {
        if ($tokens > $this->capacity) {
            throw new InvalidArgumentException('Requested tokens exceed the capacity of the token bucket.');
        }

        // overflow the bucket
        $this->doConsume($this->capacity);

        while (! $this->doConsume($tokens)) {
            $remainingTime = $this->getRemainingTimeUntilNextToken($tokens);

            $us = (int) ($remainingTime * 1000000);

            Sleep::usleep($us);
        }

        return true;
    }

    /**
     * Override to adjust the time passed.
     */
    protected function refillTokens(): void
    {
        $now = microtime(true);
        $timePassed = max(0, $now - $this->lastRefillTime);
        $this->tokens = min($this->capacity, $this->tokens + $timePassed * $this->rate);
        $this->lastRefillTime = $now;
    }

    /**
     * Calculate the time to accumulate the required number of tokens.
     */
    private function getRemainingTimeUntilNextToken(float $tokens = 1): float
    {
        $this->refillTokens();

        return max(0, ($tokens - $this->tokens) / $this->rate);
    }
}
