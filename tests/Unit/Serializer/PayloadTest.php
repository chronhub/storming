<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Serializer;

use Storm\Serializer\Payload;

it('creates payload with given values', function (array|string $content, array|string $headers, ?int $seqNo): void {
    $payload = new Payload($content, $headers, $seqNo);

    expect($payload->content)->toBe($content)
        ->and($payload->headers)->toBe($headers)
        ->and($payload->seqNo)->toBe($seqNo);
})->with([
    'content as array' => fn (): array => ['foo' => 'bar'],
    'content as json' => fn (): string => '"{"foo":"bar"}"',
])->with([
    'headers as array' => fn (): array => ['key' => 'value'],
    'headers as json' => fn (): string => '"{"key":"value"}"',
    'allow empty headers' => fn (): array => [],
])->with([
    'seqNo' => fn (): int => 123,
    'null seqNum' => fn (): null => null,
]);

it('serialize payload to json', function (): void {
    $payload = new Payload(['some' => 'content'], ['key' => 'value'], 123);

    expect($payload->jsonSerialize())
        ->toHaveKeys(['content', 'headers', 'seqNo'])
        ->toBe(['headers' => ['key' => 'value'], 'content' => ['some' => 'content'], 'seqNo' => 123]);
});
