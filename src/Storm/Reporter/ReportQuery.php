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
        $story = $this->processStory($message);

        return $story->promise();
    }
}
