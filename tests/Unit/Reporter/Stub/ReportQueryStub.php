<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Reporter\Stub;

use React\Promise\PromiseInterface;
use Storm\Contract\Reporter\Reporter;
use Storm\Reporter\Attribute\AsReporter;
use Storm\Reporter\HasConstructableReporter;

#[AsReporter]
final class ReportQueryStub implements Reporter
{
    use HasConstructableReporter;

    public function relay(object|array $message): PromiseInterface
    {
        $story = $this->dispatch($message);

        return $story->promise();
    }
}
