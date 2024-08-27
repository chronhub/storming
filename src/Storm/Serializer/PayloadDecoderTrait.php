<?php

declare(strict_types=1);

namespace Storm\Serializer;

use InvalidArgumentException;
use stdClass;
use Storm\Contract\Message\EventHeader;
use Storm\Stream\StreamPosition;

use function is_string;
use function json_decode;
use function json_encode;

trait PayloadDecoderTrait
{
    /**
     * @return array{0: array, 1: array, 2: positive-int|null}
     */
    protected function decodePartsIfNeeded(array|stdClass $payload): array
    {
        $payload = $this->convertDataToArray($payload);

        $header = $payload['header'];
        $content = $payload['content'];
        $position = $payload['position'] ?? null;

        if (is_string($header)) {
            $header = $this->serializer()->decode($header, 'json');
        }

        if (is_string($content)) {
            $content = $this->serializer()->decode($content, 'json');
        }

        $header = $this->ensureHeaderHasInternalPosition($header, $position);

        return [$header, $content, $position];
    }

    protected function convertDataToArray(array|stdClass $data): array
    {
        if ($data instanceof stdClass) {
            $data = json_decode(json_encode($data), true);
        }

        return $data;
    }

    /**
     * Add internal position to header if not already set.
     *
     * @throws InvalidArgumentException When not null, position is not a positive integer
     */
    protected function ensureHeaderHasInternalPosition(array $header, ?int $position): array
    {
        if ($position !== null && ! isset($header[EventHeader::INTERNAL_POSITION])) {
            $header[EventHeader::INTERNAL_POSITION] = StreamPosition::fromValue($position)->value;
        }

        return $header;
    }
}
