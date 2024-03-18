<?php

declare(strict_types=1);

namespace Storm\Chronicler\Connection;

use Illuminate\Database\Connection;
use Storm\Chronicler\Exceptions\TransactionAlreadyStarted;
use Storm\Chronicler\Exceptions\TransactionNotStarted;
use Throwable;

trait TransactionalStoreTrait
{
    public function beginTransaction(): void
    {
        if ($this->inTransaction()) {
            throw new TransactionAlreadyStarted('Transaction already started');
        }

        $this->connection->beginTransaction();
    }

    public function commitTransaction(): void
    {
        if (! $this->inTransaction()) {
            throw new TransactionNotStarted('Transaction not started');
        }

        $this->connection->commit();
    }

    public function rollbackTransaction(): void
    {
        if (! $this->inTransaction()) {
            throw new TransactionNotStarted('Transaction not started');
        }

        $this->connection->rollBack();
    }

    public function transactional(callable $callback): bool|array|string|int|float|object
    {
        $this->beginTransaction();

        try {
            $result = $callback($this);

            $this->commitTransaction();

            return $result;
        } catch (Throwable $exception) {
            $this->rollbackTransaction();

            throw $exception;
        }
    }

    public function inTransaction(): bool
    {
        return $this->connection()->transactionLevel() > 0;
    }

    abstract protected function connection(): Connection;
}
