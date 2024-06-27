<?php

declare(strict_types=1);

namespace Storm\Contract\Chronicler;

use Storm\Chronicler\Exceptions\TransactionAlreadyStarted;
use Storm\Chronicler\Exceptions\TransactionNotStarted;
use Throwable;

interface TransactionalChronicler extends Chronicler
{
    /**
     * Begin a transaction.
     *
     * @throws TransactionAlreadyStarted
     * @throws Throwable
     */
    public function beginTransaction(): void;

    /**
     * Commit a transaction.
     *
     * @throws TransactionNotStarted
     * @throws Throwable
     */
    public function commitTransaction(): void;

    /**
     * Rollback a transaction.
     *
     * @throws TransactionNotStarted
     * @throws Throwable
     */
    public function rollbackTransaction(): void;

    /**
     * Apply a callback within a transaction.
     *
     * @throws TransactionAlreadyStarted
     * @throws TransactionNotStarted
     * @throws Throwable
     */
    public function transactional(callable $callback): bool|array|string|int|float|object;

    /**
     * Check if a transaction is currently active.
     */
    public function inTransaction(): bool;
}
