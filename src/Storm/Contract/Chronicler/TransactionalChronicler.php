<?php

declare(strict_types=1);

namespace Storm\Contract\Chronicler;

use Storm\Chronicler\Exceptions\TransactionAlreadyStarted;
use Storm\Chronicler\Exceptions\TransactionNotStarted;
use Throwable;

interface TransactionalChronicler extends Chronicler
{
    /**
     * @throws TransactionAlreadyStarted
     * @throws Throwable
     */
    public function beginTransaction(): void;

    /**
     * @throws TransactionNotStarted
     * @throws Throwable
     */
    public function commitTransaction(): void;

    /**
     * @throws TransactionNotStarted
     * @throws Throwable
     */
    public function rollbackTransaction(): void;

    /**
     * @throws TransactionAlreadyStarted
     * @throws TransactionNotStarted
     * @throws Throwable
     */
    public function transactional(callable $callback): bool|array|string|int|float|object;

    public function inTransaction(): bool;
}
