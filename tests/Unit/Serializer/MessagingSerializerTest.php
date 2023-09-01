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

beforeEach(function () {
    $this->serializer = mock(Serializer::class);
    $this->contentSerializer = mock(ContentSerializer::class);
    $this->messagingSerializer = new MessagingSerializer($this->serializer, $this->contentSerializer);
});

afterEach(function () {
    $this->serializer = null;
    $this->contentSerializer = null;
    $this->messagingSerializer = null;
});

it('serializes a message', function (): void {
    $content = ['foo' => 'bar'];
    $headers = ['key' => 'value'];
    $command = SomeCommand::fromContent($content)->withHeaders($headers);

    $message = new Message($command);

    $this->serializer->shouldReceive('normalize')->with($headers, 'json')->andReturn($headers);
    $this->contentSerializer->shouldReceive('serialize')->andReturn($content);

    $payload = $this->messagingSerializer->serializeMessage($message);

    expect($payload)->toBeInstanceOf(Payload::class)
        ->and($payload->content)->toBe($content)
        ->and($payload->headers)->toBe($headers)
        ->and($payload->seqNo)->toBeNull();
});

it('deserializes payload to messaging instance', function (string $source) {
    $payload = new Payload(['some' => 'content'], [Header::EVENT_TYPE => $source]);

    if (! method_exists($source, 'fromContent')) {
        throw new RuntimeException('invalid data provided');
    }

    $this->contentSerializer->shouldReceive('deserialize')->andReturn($source::fromContent(['some' => 'content']));
    $this->serializer->shouldNotHaveBeenCalled();

    $messaging = $this->messagingSerializer->deserializePayload($payload);

    expect($messaging)->toBeInstanceOf($source)
        ->and($messaging->toContent())->toBe(['some' => 'content'])
        ->and($messaging->headers())->toBe([Header::EVENT_TYPE => $source]);

})->with([
    'command' => SomeCommand::class,
    'event' => SomeEvent::class,
]);

describe('raises exception with invalid event type header', function (): void {
    it('when missing', function (): void {
        $this->messagingSerializer->deserializePayload(new Payload(['some' => 'content'], []));
    })->throws(InvalidArgumentException::class, 'Missing event type header string to deserialize payload');

    it('when not a string', function (mixed $value): void {
        $this->messagingSerializer->deserializePayload(new Payload(['some' => 'content'], [Header::EVENT_TYPE => $value]));
    })->with([
        'null' => fn () => null,
        'object' => fn () => new stdClass(),
        'array' => fn () => [],
        'int' => fn () => 42,
    ])->throws(InvalidArgumentException::class, 'Missing event type header string to deserialize payload');
});
