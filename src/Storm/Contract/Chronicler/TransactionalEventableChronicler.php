<?php

declare(strict_types=1);

namespace Storm\Contract\Chronicler;

interface TransactionalEventableChronicler extends EventableChronicler, TransactionalChronicler
{
    public const string BEGIN_TRANSACTION_EVENT = 'begin_transaction';

    public const string COMMIT_TRANSACTION_EVENT = 'commit_transaction';

    public const string ROLLBACK_TRANSACTION_EVENT = 'rollback_transaction';
}
