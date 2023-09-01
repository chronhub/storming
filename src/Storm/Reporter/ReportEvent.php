<?php

declare(strict_types=1);

namespace Storm\Reporter;

use Storm\Contract\Reporter\EventReporter;

final class ReportEvent implements EventReporter
{
    use HasConstructableReporter;

    public function relay(object|array $message): void
    {
        $story = $this->tracker->newStory(self::DISPATCH_EVENT);

        $story->withTransientMessage($message);

        $this->relayMessage($story);

        if ($story->hasException()) {
            throw $story->exception();
        }
    }
}
