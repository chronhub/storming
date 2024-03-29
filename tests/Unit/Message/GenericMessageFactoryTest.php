<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Message;

use Mockery;
use stdClass;
use Storm\Contract\Message\Messaging;
use Storm\Contract\Serializer\MessageSerializer;
use Storm\Message\GenericMessageFactory;
use Storm\Message\Message;
use Storm\Serializer\Payload;
use Storm\Tests\Stubs\Double\Message\SomeCommand;
use Storm\Tests\Stubs\Double\Message\SomeEvent;
use Storm\Tests\Stubs\Double\Message\SomeQuery;

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertInstanceOf;
use function PHPUnit\Framework\assertNull;

beforeEach(function (): void {
    $this->messagingSerializer = mock(MessageSerializer::class);
    $this->messageFactory = new GenericMessageFactory($this->messagingSerializer);
});

afterEach(function (): void {
    $this->messagingSerializer = null;
    $this->messageFactory = null;
});

it('create message from array', function (string $messaging): void {
    $payload = [
        'content' => ['name' => 'steph bug'],
        'headers' => ['key' => 'value'],
    ];

    /** @var Messaging $messaging */
    $stub = $messaging::fromContent($payload['content'])->withHeaders($payload['headers']);

    $this->messagingSerializer
        ->shouldReceive('deserialize')
        ->with(Mockery::on(static function ($expected) use ($payload): bool {
            assertInstanceOf(Payload::class, $expected);
            assertEquals($expected->content, $payload['content']);
            assertEquals($expected->headers, $payload['headers']);
            assertNull($expected->seqNo);

            return true;
        }))->andReturn($stub);

    $message = $this->messageFactory->createMessageFrom($payload);

    expect($message)->toBeInstanceOf(Message::class)
        ->and($message->event()::class)->toBe($messaging)
        ->and($message->event()->toContent())->toBe(['name' => 'steph bug'])
        ->and($message->headers())->toBe(['key' => 'value']);
})->with([
    'command' => SomeCommand::class,
    'event' => SomeEvent::class,
]);

it('create message from object', function () {

    $event = new stdClass();

    $message = new Message($event, ['key' => 'value']);
    $newMessage = $this->messageFactory->createMessageFrom($message);

    expect($newMessage)->toEqual($message)->not()->toBe($message)
        ->and($newMessage->event())->toEqual($event)->not()->toBe($event);
});

it('create message from message instance', function (string $messaging) {
    /** @var Messaging $messaging */
    $event = $messaging::fromContent(['foo' => 'bar'])->withHeaders(['key' => 'value']);

    $message = new Message($event);

    $newMessage = $this->messageFactory->createMessageFrom($message);

    expect($newMessage)->toEqual($message)->not()->toBe($message);
})->with([
    'command' => SomeCommand::class,
    'event' => SomeEvent::class,
    'query' => SomeQuery::class,
]);
