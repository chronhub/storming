<?php

declare(strict_types=1);

namespace Storm\Chronicler;

use Storm\Chronicler\Exceptions\TransactionAlreadyStarted;
use Storm\Chronicler\Exceptions\TransactionNotStarted;
use Storm\Contract\Tracker\TransactionalStreamStory;

final class TransactionalStreamDraft extends StreamDraft implements TransactionalStreamStory
{
    public function hasTransactionNotStarted(): bool
    {
        return $this->exception instanceof TransactionNotStarted;
    }

    public function hasTransactionAlreadyStarted(): bool
    {
        return $this->exception instanceof TransactionAlreadyStarted;
    }
}
