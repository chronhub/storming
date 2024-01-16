<?php

declare(strict_types=1);

namespace Storm\Reporter;

use React\Promise\PromiseInterface;
use Storm\Contract\Reporter\Reporter;

final class ReportQuery implements Reporter
{
    use HasConstructableReporter;

    public function relay(object|array $message): PromiseInterface
    {
        $story = $this->dispatch($message);

        return $story->promise();
    }
}
