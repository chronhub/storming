<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Support;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Schema\Builder as SchemaBuilder;
use Storm\Projector\Support\ReadModel\ReadModelConnection;

beforeEach(function () {
    $this->connection = $connection = mock(Connection::class);
    $this->queryBuilder = mock(QueryBuilder::class);
    $this->schemaBuilder = mock(SchemaBuilder::class);
    $this->readModel = new class($connection) extends ReadModelConnection
    {
        public function getQuery(): QueryBuilder
        {
            return $this->query();
        }

        protected function up(): callable
        {
            return fn () => 'migration';
        }

        protected function tableName(): string
        {
            return 'some_table_name';
        }
    };
});

test('get query builder', function () {
    $this->connection->expects('table')->with('some_table_name')->andReturn($this->queryBuilder);

    expect($this->readModel->getQuery())->toBe($this->queryBuilder);
});

test('initialize read model', function () {
    $this->connection->expects('getSchemaBuilder')->andReturn($this->schemaBuilder);

    $this->schemaBuilder->expects('create')->withArgs(
        function (string $tableName, callable $up) {
            return $tableName === 'some_table_name' && $up() === 'migration';
        }
    );

    $this->readModel->initialize();
});

test('is initialized', function (bool $isInitialized) {
    $this->connection->expects('getSchemaBuilder')->andReturn($this->schemaBuilder);

    $this->schemaBuilder->expects('hasTable')->with('some_table_name')->andReturn($isInitialized);

    expect($this->readModel->isInitialized())->toBe($isInitialized);
})->with([[true], [false]]);

test('reset', function () {
    $this->connection->expects('getSchemaBuilder')->andReturn($this->schemaBuilder);

    $this->schemaBuilder->expects('disableForeignKeyConstraints');
    $this->connection->expects('table')->with('some_table_name')->andReturn($this->queryBuilder);
    $this->queryBuilder->expects('truncate')->andReturn($this->queryBuilder);
    $this->schemaBuilder->expects('enableForeignKeyConstraints');

    $this->readModel->reset();
});

test('down', function () {
    $this->connection->expects('getSchemaBuilder')->andReturn($this->schemaBuilder);

    $this->schemaBuilder->expects('disableForeignKeyConstraints');
    $this->schemaBuilder->expects('drop')->with('some_table_name');
    $this->schemaBuilder->expects('enableForeignKeyConstraints');

    $this->readModel->down();
});
