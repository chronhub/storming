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
     * @throws TransactionAlreadyStarted when the transaction already started
     * @throws Throwable                 when any other error
     */
    public function beginTransaction(): void;

    /**
     * Commit a transaction.
     *
     * @throws TransactionNotStarted when the transaction is not started
     * @throws Throwable             when any other error
     */
    public function commitTransaction(): void;

    /**
     * Rollback a transaction.
     *
     * @throws TransactionNotStarted when the transaction is not started
     * @throws Throwable             when any other error
     */
    public function rollbackTransaction(): void;

    /**
     * Apply a callback within a transaction or a nested transaction.
     *
     * It should be used for story processing as it could handle sync and async
     * in an atomic way.
     *
     * @throws TransactionAlreadyStarted when the transaction already started
     * @throws TransactionNotStarted     when the transaction is not started
     * @throws Throwable                 when any other error
     */
    public function transactional(callable $callback): bool|array|string|int|float|object;

    /**
     * Check if a transaction is currently active.
     */
    public function inTransaction(): bool;
}
