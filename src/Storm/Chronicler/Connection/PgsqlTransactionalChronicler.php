<?php

declare(strict_types=1);

namespace Storm\Chronicler\Connection;

use Illuminate\Database\Connection;
use Storm\Chronicler\Attribute\AsChronicler;
use Storm\Contract\Chronicler\Chronicler;
use Storm\Contract\Chronicler\TransactionalChronicler;

#[AsChronicler(
    connection: 'pgsql',
    abstract: 'chronicler.event.transactional.standard.pgsql',
    firstClass: PgsqlChronicler::class
)]
final class PgsqlTransactionalChronicler implements TransactionalChronicler
{
    use ProvideChroniclerTrait;
    use TransactionalStoreTrait;

    private Connection $connection;

    public function __construct(protected readonly Chronicler $chronicler) {}

    public function setConnection(Connection $connection): void
    {
        $this->connection = $connection;
    }

    protected function connection(): Connection
    {
        return $this->connection;
    }
}
