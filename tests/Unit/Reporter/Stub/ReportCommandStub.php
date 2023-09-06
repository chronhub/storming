<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Reporter\Stub;

use Storm\Contract\Reporter\Reporter;
use Storm\Reporter\Attribute\AsReporter;
use Storm\Reporter\DelegateToQueue;
use Storm\Reporter\HasProcessStory;

#[AsReporter]
final class ReportCommandStub implements Reporter
{
    use DelegateToQueue;
    use HasProcessStory;

    public function relay(object|array $message): void
    {
        $this->queueAndProcess($message);
    }
}
