<?php

declare(strict_types=1);

namespace Storm\Serializer;

use InvalidArgumentException;
use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Message\EventHeader;
use Storm\Contract\Serializer\StrategyMapper;

use function is_array;

final readonly class StandardStrategyMapper implements StrategyMapper
{
    public function __construct(private SystemClock $clock)
    {
    }

    public function map(mixed $data, ?string $streamName): array
    {
        if (! is_array($data)) {
            throw new InvalidArgumentException('Data must be an array');
        }

        if ($streamName === null) {
            throw new InvalidArgumentException('Stream name is required to map data');
        }

        return [
            'stream_name' => $streamName,
            'type' => $data['header'][EventHeader::AGGREGATE_TYPE],
            'id' => $data['header'][EventHeader::AGGREGATE_ID],
            'version' => $data['header'][EventHeader::AGGREGATE_VERSION],
            'header' => $data['header'],
            'content' => $data['content'],
            'created_at' => $this->clock->generate(),
        ];
    }
}
