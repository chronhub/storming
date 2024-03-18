<?php

declare(strict_types=1);

namespace Storm\Projector\Support\Token;

use Storm\Contract\Projector\TokenBucket;
use Storm\Projector\Exception\InvalidArgumentException;

use function microtime;
use function min;

abstract class AbstractTokenBucket implements TokenBucket
{
    protected float $tokens;

    protected float $lastRefillTime;

    public function __construct(
        public readonly float $capacity,
        public readonly float $rate
    ) {
        if ($capacity <= 0 || $rate <= 0) {
            throw new InvalidArgumentException('Capacity and rate must be greater than zero.');
        }

        $this->tokens = $capacity;
        $this->lastRefillTime = microtime(true);
    }

    public function remainingTokens(): int|float
    {
        return $this->tokens;
    }

    public function getCapacity(): int|float
    {
        return $this->capacity;
    }

    public function getRate(): int|float
    {
        return $this->rate;
    }

    protected function doConsume(float $tokens): bool
    {
        $this->refillTokens();

        if ($this->tokens >= $tokens) {
            $this->tokens -= $tokens;

            return true;
        }

        return false;
    }

    /**
     * Refill the token bucket.
     */
    protected function refillTokens(): void
    {
        $now = microtime(true);
        $timePassed = $now - $this->lastRefillTime;
        $this->tokens = min($this->capacity, $this->tokens + $timePassed * $this->rate);
        $this->lastRefillTime = $now;
    }
}
