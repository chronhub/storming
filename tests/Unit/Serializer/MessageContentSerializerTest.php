<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Serializer;

use InvalidArgumentException;
use Storm\Serializer\MessageContentSerializer;
use Storm\Serializer\Payload;
use Storm\Tests\Stubs\Double\Message\SomeCommand;
use Storm\Tests\Stubs\Double\Message\SomeEvent;
use Storm\Tests\Stubs\Double\Message\SomeQuery;

it('serialize message content', function (): void {
    $command = SomeCommand::fromContent(['steph' => 'bug']);
    $serializer = new MessageContentSerializer();

    expect($serializer->serialize($command))->toBe(['steph' => 'bug']);
});

it('deserialize content payload to messaging instance', function (string $source): void {
    $serializer = new MessageContentSerializer();
    $messaging = $serializer->deserialize($source, new Payload(['some' => 'content'], []));

    expect($messaging::class)->toBe($source)
        ->and($messaging->toContent())->toBe(['some' => 'content'])
        ->and($messaging->headers())->toBeEmpty();
})->with([
    'command' => SomeCommand::class,
    'event' => SomeEvent::class,
    'query' => SomeQuery::class,
]);

it('raise exception when source to deserialize is not subclass of messaging', function (): void {
    $serializer = new MessageContentSerializer();

    $serializer->deserialize('foo', new Payload([], []));
})->throws(InvalidArgumentException::class, 'Only class which implement Messaging contract can be deserialized');
