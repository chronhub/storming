<?php

declare(strict_types=1);

namespace Storm\Reporter\Subscriber;

use Closure;
use Storm\Contract\Message\Header;
use Storm\Contract\Tracker\MessageStory;

final readonly class NameReporter
{
    public function __construct(private string $name)
    {
    }

    public function __invoke(): Closure
    {
        return function (MessageStory $story): void {
            if ($story->message()->hasNot(Header::REPORTER_ID)) {
                $message = $story->message()->withHeader(Header::REPORTER_ID, $this->name);

                $story->withMessage($message);
            }
        };
    }
}
