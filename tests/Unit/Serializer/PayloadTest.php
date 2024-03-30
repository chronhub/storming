<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Serializer;

use Storm\Serializer\Payload;

it('creates payload with given values', function (array|string $content, array|string $header, ?int $seqNo): void {
    $payload = new Payload($header, $content, $seqNo);

    expect($payload->content)->toBe($content)
        ->and($payload->header)->toBe($header)
        ->and($payload->position)->toBe($seqNo);
})->with([
    'content as array' => fn (): array => ['foo' => 'bar'],
    'content as json' => fn (): string => '"{"foo":"bar"}"',
    'allow empty content' => fn (): array => [],
])->with([
    'headers as array' => fn (): array => ['key' => 'value'],
    'headers as json' => fn (): string => '"{"key":"value"}"',
    'allow empty headers' => fn (): array => [],
])->with([
    'seqNo' => fn (): int => 123,
    'null seqNo' => fn (): null => null,
]);

it('serializes payload', function (): void {
    $payload = new Payload(['header' => 'value'], ['content' => 'value'], 123);

    expect($payload->jsonSerialize())
        ->toBeArray()
        ->toHaveKeys(['content', 'header', 'position'])
        ->toBe(['header' => ['header' => 'value'], 'content' => ['content' => 'value'], 'position' => 123]);
});
