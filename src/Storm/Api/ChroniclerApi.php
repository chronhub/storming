<?php

declare(strict_types=1);

namespace Storm\Chronicler\Api;

use Illuminate\Database\Connection;
use Storm\Chronicler\Attribute\AsChronicler;
use Storm\Chronicler\Connection\PgsqlChronicler;
use Storm\Chronicler\Connection\ProvideChroniclerTrait;
use Storm\Chronicler\Connection\TransactionalStoreTrait;
use Storm\Contract\Chronicler\Chronicler;
use Storm\Contract\Chronicler\TransactionalChronicler;

#[AsChronicler(
    connection: 'pgsql',
    abstract: 'chronicler.api.standard',
    eventable: false,
    firstClass: PgsqlChronicler::class
)]
final class ChroniclerApi implements TransactionalChronicler
{
    use ProvideChroniclerTrait;
    use TransactionalStoreTrait;

    private Connection $connection;

    public function __construct(protected readonly Chronicler $chronicler)
    {
    }

    public function setConnection(Connection $connection): void
    {
        $this->connection = $connection;
    }

    protected function connection(): Connection
    {
        return $this->connection;
    }
}
