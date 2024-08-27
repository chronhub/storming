<?php

declare(strict_types=1);

namespace Storm\Chronicler\Database;

use Illuminate\Database\Connection;
use Storm\Contract\Chronicler\Chronicler;
use Storm\Contract\Chronicler\ChroniclerDecorator;
use Storm\Contract\Chronicler\DatabaseChronicler;
use Storm\Contract\Chronicler\TransactionalChronicler;

final readonly class PgsqlTransactionalEventStore implements ChroniclerDecorator, DatabaseChronicler, TransactionalChronicler
{
    use ProvideChroniclerTrait;
    use TransactionalStoreTrait;

    public function __construct(private DatabaseChronicler $chronicler) {}

    public function getConnection(): Connection
    {
        return $this->chronicler->getConnection();
    }

    public function innerChronicler(): Chronicler
    {
        return $this->chronicler;
    }
}
