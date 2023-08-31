<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Serializer;

use InvalidArgumentException;
use RuntimeException;
use stdClass;
use Storm\Contract\Message\Header;
use Storm\Contract\Serializer\ContentSerializer;
use Storm\Message\Message;
use Storm\Serializer\MessagingSerializer;
use Storm\Serializer\Payload;
use Storm\Tests\Stubs\Double\Message\SomeCommand;
use Storm\Tests\Stubs\Double\Message\SomeEvent;
use Symfony\Component\Serializer\Serializer;

use function method_exists;

it('serializes a message', function (): void {
    $contentSerializer = mock(ContentSerializer::class);
    $serializer = mock(Serializer::class);

    $content = ['foo' => 'bar'];
    $headers = ['key' => 'value'];
    $command = SomeCommand::fromContent($content)->withHeaders($headers);

    $message = new Message($command);

    $serializer->shouldReceive('normalize')->with($headers, 'json')->andReturn($headers);
    $contentSerializer->shouldReceive('serialize')->andReturn($content);

    $messagingSerializer = new MessagingSerializer($serializer, $contentSerializer);

    $payload = $messagingSerializer->serializeMessage($message);

    expect($payload)->toBeInstanceOf(Payload::class)
        ->and($payload->content)->toBe($content)
        ->and($payload->headers)->toBe($headers)
        ->and($payload->seqNo)->toBeNull();
});

it('deserializes payload to messaging instance', function (string $source) {
    $payload = new Payload(['some' => 'content'], [Header::EVENT_TYPE => $source]);

    $contentSerializer = mock(ContentSerializer::class);
    $serializer = mock(Serializer::class);
    $serializer = new MessagingSerializer($serializer, $contentSerializer);

    if (! method_exists($source, 'fromContent')) {
        throw new RuntimeException('invalid data provided');
    }
    $contentSerializer->shouldReceive('deserialize')->andReturn($source::fromContent(['some' => 'content']));

    $messaging = $serializer->deserializePayload($payload);

    expect($messaging)->toBeInstanceOf($source)
        ->and($messaging->toContent())->toBe(['some' => 'content'])
        ->and($messaging->headers())->toBe([Header::EVENT_TYPE => $source]);

})->with([
    'command' => SomeCommand::class,
    'event' => SomeEvent::class,
]);

it('raises exception when event type header is missing', function (): void {
    $serializer = new MessagingSerializer(mock(Serializer::class), mock(ContentSerializer::class));
    $serializer->deserializePayload(new Payload(['some' => 'content'], []));
})->throws(InvalidArgumentException::class, 'Missing event type header string to deserialize payload');

it('raises exception when event type header is not a string', function (mixed $value): void {
    $serializer = new MessagingSerializer(mock(Serializer::class), mock(ContentSerializer::class));
    $serializer->deserializePayload(new Payload(['some' => 'content'], [Header::EVENT_TYPE => $value]));
})->with([
    'null' => fn () => null,
    'object' => fn () => new stdClass(),
    'array' => fn () => [],
    'int' => fn () => 42,
])->throws(InvalidArgumentException::class, 'Missing event type header string to deserialize payload');
