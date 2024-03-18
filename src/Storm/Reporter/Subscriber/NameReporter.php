<?php

declare(strict_types=1);

namespace Storm\Reporter\Subscriber;

use Closure;
use Storm\Contract\Message\Header;
use Storm\Contract\Tracker\MessageStory;

// fixMe
final readonly class NameReporter
{
    public function __construct(private string $reporterId)
    {
    }

    public function __invoke(): Closure
    {
        return function (MessageStory $story): void {
            $message = $story->message();

            if ($message->hasNot(Header::REPORTER_ID)) {
                $message = $message->withHeader(Header::REPORTER_ID, $this->reporterId);

                $story->withMessage($message);
            }
        };
    }
}
