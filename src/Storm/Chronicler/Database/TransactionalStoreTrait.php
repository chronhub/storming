<?php

declare(strict_types=1);

namespace Storm\Chronicler\Database;

use Storm\Chronicler\Exceptions\TransactionAlreadyStarted;
use Storm\Chronicler\Exceptions\TransactionNotStarted;
use Storm\Contract\Chronicler\DatabaseChronicler;
use Storm\Contract\Chronicler\TransactionalChronicler;

/**
 * @phpstan-require-implements TransactionalChronicler
 * @phpstan-require-implements DatabaseChronicler
 */
trait TransactionalStoreTrait
{
    public function beginTransaction(): void
    {
        if ($this->inTransaction()) {
            throw new TransactionAlreadyStarted('Transaction already started');
        }

        $this->getConnection()->beginTransaction();
    }

    public function commitTransaction(): void
    {
        if (! $this->inTransaction()) {
            throw new TransactionNotStarted('Transaction not started');
        }

        $this->getConnection()->commit();
    }

    public function rollbackTransaction(): void
    {
        if (! $this->inTransaction()) {
            throw new TransactionNotStarted('Transaction not started');
        }

        $this->getConnection()->rollBack();
    }

    public function transactional(callable $callback): bool|array|string|int|float|object
    {
        $this->getConnection()->transaction($callback);

        return true;
    }

    public function inTransaction(): bool
    {
        return $this->getConnection()->transactionLevel() > 0;
    }
}
