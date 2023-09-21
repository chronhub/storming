<?php

declare(strict_types=1);

namespace Storm\Reporter\Listener;

use Storm\Contract\Message\Header;
use Storm\Contract\Reporter\Reporter;
use Storm\Contract\Tracker\Listener;
use Storm\Contract\Tracker\MessageStory;

final readonly class NameReporterListener implements Listener
{
    public function __construct(private string $reporterName)
    {
    }

    public function name(): string
    {
        return Reporter::DISPATCH_EVENT;
    }

    public function priority(): int
    {
        return 98000;
    }

    public function story(): callable
    {
        return function (MessageStory $story): void {
            if ($story->message()->hasNot(Header::REPORTER_ID)) {
                $message = $story->message()->withHeader(Header::REPORTER_ID, $this->reporterName);

                $story->withMessage($message);
            }
        };
    }

    public function origin(): string
    {
        return self::class;
    }
}
