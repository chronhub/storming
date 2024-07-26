<?php

declare(strict_types=1);

namespace Storm\Contract\Tracker;

interface TransactionalStreamStory extends StreamStory
{
    /**
     * Check if the transaction has not started.
     */
    public function hasTransactionNotStarted(): bool;

    /**
     * Check if the transaction has already started.
     */
    public function hasTransactionAlreadyStarted(): bool;
}
