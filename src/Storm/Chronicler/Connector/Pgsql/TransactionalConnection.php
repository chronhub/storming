<?php

declare(strict_types=1);

namespace Storm\Chronicler\Connector\Pgsql;

use Storm\Chronicler\Connector\ConnectionManager;
use Storm\Chronicler\Database\PgsqlTransactionalEventStore;
use Storm\Contract\Chronicler\DatabaseChronicler;

final readonly class TransactionalConnection implements ConnectionManager
{
    public function __construct(private DatabaseChronicler $chronicler) {}

    public function create(): DatabaseChronicler
    {
        return new PgsqlTransactionalEventStore($this->chronicler);
    }
}
