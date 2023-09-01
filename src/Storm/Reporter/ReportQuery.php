<?php

declare(strict_types=1);

namespace Storm\Reporter;

use React\Promise\PromiseInterface;
use Storm\Contract\Reporter\QueryReporter;

final class ReportQuery implements QueryReporter
{
    use HasConstructableReporter;

    public function relay(object|array $message): PromiseInterface
    {
        $story = $this->tracker->newStory(self::DISPATCH_EVENT);

        $story->withTransientMessage($message);

        $this->relayMessage($story);

        if ($story->hasException()) {
            throw $story->exception();
        }

        return $story->promise();
    }
}
