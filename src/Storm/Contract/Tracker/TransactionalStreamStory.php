<?php

declare(strict_types=1);

namespace Storm\Contract\Tracker;

interface TransactionalStreamStory extends StreamStory
{
    public function hasTransactionNotStarted(): bool;

    public function hasTransactionAlreadyStarted(): bool;
}
