<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Chronicler;

use Error;
use Exception;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\LazyCollection;
use PDOException;
use stdClass;
use Storm\Chronicler\Database\LazyQueryLoader;
use Storm\Chronicler\Exceptions\NoStreamEventReturn;
use Storm\Chronicler\Exceptions\StreamNotFound;
use Storm\Contract\Serializer\StreamEventSerializer;
use Storm\Stream\StreamName;
use Storm\Tests\Stubs\Double\Message\SomeEvent;
use Throwable;

use function iterator_to_array;

beforeEach(function () {
    $this->connection = mock(Connection::class);
    $this->builder = mock(Builder::class);
    $this->serializer = mock(StreamEventSerializer::class);
    $this->streamName = new StreamName('stream_name');
    $this->instance = new LazyQueryLoader($this->serializer);
});

it('load stream event from cursor', function () {
    $event = new stdClass;
    $event->headers = ['foo' => 'bar'];
    $event->content = ['some' => 'content'];

    $collection = new LazyCollection([$event]);
    $this->builder->shouldReceive('cursor')->andReturn($collection);

    $expectedEvent = SomeEvent::fromContent($event->content)->withHeaders($event->headers);
    $this->serializer->shouldReceive('deserialize')->andReturn($expectedEvent);

    $streamEvents = $this->instance->load($this->builder, $this->streamName);

    $events = iterator_to_array($streamEvents);

    expect($events)->toHaveCount(1)
        ->and($events[0])->toBe($expectedEvent)
        ->and($streamEvents->getReturn())->toBe(1);
});

it('raise exception when no events are returned', function () {
    $collection = new LazyCollection([]);

    $this->builder->shouldReceive('cursor')->andReturn($collection);
    $this->serializer->shouldNotReceive('toDomainEvent');

    $streamEvents = $this->instance->load($this->builder, $this->streamName);

    $streamEvents->current();
})->throws(NoStreamEventReturn::class);

it('assert stream not found exception when query exception', function () {
    $pdoException = new PDOException('foo', 42);
    $queryException = new QueryException('connection', 'sql', [], $pdoException);

    $this->connection->shouldReceive('cursor')->andThrow($queryException);
    $this->builder->connection = $this->connection;

    $this->builder->shouldReceive('cursor')->andThrow($queryException);
    $this->serializer->shouldNotReceive('toDomainEvent');

    try {
        $streamEvents = $this->instance->load($this->builder, $this->streamName);
        $streamEvents->current();
    } catch (StreamNotFound $exception) {
        expect($exception::class)->toBe(StreamNotFound::class)
            ->and($exception::class)->not()->toBe(NoStreamEventReturn::class);
    }
});

it('raise exception when not query exception', function (Throwable $exception) {
    $this->connection->shouldReceive('cursor')->andThrow($exception);
    $this->builder->connection = $this->connection;

    $this->builder->shouldReceive('cursor')->andThrow($exception);
    $this->serializer->shouldNotReceive('toDomainEvent');

    try {
        $streamEvents = $this->instance->load($this->builder, $this->streamName);
        $streamEvents->current();
    } catch (Throwable $exception) {
        expect($exception)->toBe($exception);
    }
})->with([
    [new Exception('foo')],
    [new Error('foo')],
]);
