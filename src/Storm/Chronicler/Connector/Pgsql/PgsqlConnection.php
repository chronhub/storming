<?php

declare(strict_types=1);

namespace Storm\Chronicler\Connector\Pgsql;

use Illuminate\Database\Connection;
use Storm\Chronicler\Connector\ConnectionManager;
use Storm\Chronicler\Database\PgsqlEventStore;
use Storm\Contract\Chronicler\DatabaseChronicler;
use Storm\Contract\Chronicler\DatabaseQueryLoader;
use Storm\Contract\Chronicler\EventStreamProvider;
use Storm\Contract\Chronicler\StreamPersistence;

final readonly class PgsqlConnection implements ConnectionManager
{
    public function __construct(
        private Connection $connection,
        private EventStreamProvider $eventStreamProvider,
        private StreamPersistence $streamPersistence,
        private DatabaseQueryLoader $streamEventLoader,
        private ?string $tableName = null,
    ) {}

    public function create(): DatabaseChronicler
    {
        return new PgsqlEventStore(
            $this->connection,
            $this->eventStreamProvider,
            $this->streamPersistence,
            $this->streamEventLoader,
            $this->tableName ?? PgsqlEventStore::DEFAULT_TABLE_NAME,
        );
    }
}
