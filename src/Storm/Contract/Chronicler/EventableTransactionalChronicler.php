<?php

declare(strict_types=1);

namespace Storm\Contract\Chronicler;

interface EventableTransactionalChronicler extends EventableChronicler, TransactionalChronicler
{
    final public const string BEGIN_TRANSACTION = 'transaction.begin';

    final public const string COMMIT_TRANSACTION = 'transaction.commit';

    final public const string ROLLBACK_TRANSACTION = 'transaction.rollback';
}
