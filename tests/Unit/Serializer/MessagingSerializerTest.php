<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Serializer;

use Storm\Contract\Message\Header;
use Storm\Contract\Message\Messaging;
use Storm\Message\Message;
use Storm\Serializer\JsonSerializerFactory;
use Storm\Serializer\MessagingNormalizer;
use Storm\Serializer\MessagingSerializer;
use Storm\Serializer\Payload;
use Storm\Tests\Stubs\Double\Message\SomeCommand;

beforeEach(function () {
    $serializer = new JsonSerializerFactory();
    $serializer->withNormalizer(new MessagingNormalizer());
    $this->messagingSerializer = new MessagingSerializer($serializer->create());
});

afterEach(function () {
    $this->serializer = null;
    $this->messagingSerializer = null;
});

it('serializes a message', function (): void {
    $content = ['foo' => 'bar'];
    $headers = [Header::EVENT_TYPE => SomeCommand::class, 'key' => 'value'];
    $command = SomeCommand::fromContent($content)->withHeaders($headers);

    $message = new Message($command);

    $payload = $this->messagingSerializer->serializeMessage($message);

    expect($payload)->toBeInstanceOf(Payload::class)
        ->and($payload->content)->toBeString()
        ->and($payload->header)->toBe($headers)
        ->and($payload->position)->toBeNull();

    $toArray = $payload->jsonSerialize();

    $deserialized = $this->messagingSerializer->deserialize($toArray);

    expect($deserialized)->toBeInstanceOf(Messaging::class)
        ->and($deserialized)->toBeInstanceOf(SomeCommand::class)
        ->and($deserialized->toContent())->toBe($content)
        ->and($deserialized->headers())->toBe($headers);
});
