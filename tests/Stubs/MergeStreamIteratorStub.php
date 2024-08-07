<?php

declare(strict_types=1);

namespace Storm\Tests\Stubs;

use Generator;
use Storm\Clock\ClockFactory;
use Storm\Contract\Message\EventHeader;
use Storm\Contract\Message\Header;
use Storm\Projector\Stream\Iterator\MergeStreamIterator;
use Storm\Projector\Stream\Iterator\StreamIterator;
use Storm\Tests\Stubs\Double\Message\SomeEvent;

final readonly class MergeStreamIteratorStub
{
    public array $expectedOrder;

    public function __construct()
    {
        $this->expectedOrder = [1, 1, 1, 2, 2, 2, 8, 8];
    }

    public function getMergeStreams(): MergeStreamIterator
    {
        $streams = collect(
            [
                [new StreamIterator($this->getStreamEventsOne()), 'stream1'],
                [new StreamIterator($this->getStreamEventsTwo()), 'stream2'],
                [new StreamIterator($this->getStreamEventsThree()), 'stream3'],
            ]
        );

        $streams = new MergeStreamIterator(ClockFactory::create(), $streams);

        expect($streams->valid())->toBeTrue()
            ->and($streams->count())->toBe(8)
            ->and($streams->numberOfIterators)->toBe(3)
            ->and($streams->numberOfEvents)->toBe(8);

        return $streams;
    }

    public function getStreamEventsOne(): Generator
    {
        $stream = 'stream1';

        yield SomeEvent::fromContent(['stream' => $stream])->withHeaders(
            [
                EventHeader::INTERNAL_POSITION => 1,
                Header::EVENT_TIME => '2024-06-20T10:22:05.000003',
                'in_order' => 3,
            ]
        );

        yield SomeEvent::fromContent(['stream' => $stream])->withHeaders(
            [
                EventHeader::INTERNAL_POSITION => 2,
                Header::EVENT_TIME => '2024-06-20T10:22:05.000006',
                'in_order' => 6,
            ]
        );

        return 2;
    }

    public function getStreamEventsTwo(): Generator
    {
        $stream = 'stream2';

        yield SomeEvent::fromContent(['stream' => $stream])->withHeaders(
            [
                EventHeader::INTERNAL_POSITION => 1,
                Header::EVENT_TIME => '2024-06-20T10:22:05.000001',
                'in_order' => 1,
            ]
        );

        yield SomeEvent::fromContent(['stream' => $stream])->withHeaders(
            [
                EventHeader::INTERNAL_POSITION => 2,
                Header::EVENT_TIME => '2024-06-20T10:22:05.000004',
                'in_order' => 4,
            ]
        );

        yield SomeEvent::fromContent(['stream' => $stream])->withHeaders(
            [
                EventHeader::INTERNAL_POSITION => 8,
                Header::EVENT_TIME => '2024-06-20T10:22:05.000008',
                'in_order' => 8,
            ]
        );

        return 3;
    }

    public function getStreamEventsThree(): Generator
    {
        $stream = 'stream3';

        yield SomeEvent::fromContent(['stream' => $stream])->withHeaders(
            [
                EventHeader::INTERNAL_POSITION => 1,
                Header::EVENT_TIME => '2024-06-20T10:22:05.000002',
                'in_order' => 2,
            ]
        );

        yield SomeEvent::fromContent(['stream' => $stream])->withHeaders(
            [
                EventHeader::INTERNAL_POSITION => 2,
                Header::EVENT_TIME => '2024-06-20T10:22:05.000005',
                'in_order' => 5,
            ]
        );

        yield SomeEvent::fromContent(['stream' => $stream])->withHeaders(
            [
                EventHeader::INTERNAL_POSITION => 8,
                Header::EVENT_TIME => '2024-06-20T10:22:05.000007',
                'in_order' => 7,
            ]
        );

        return 3;
    }
}
