<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Reporter\Stub;

use Storm\Contract\Reporter\Reporter;
use Storm\Reporter\Attribute\AsReporter;
use Storm\Reporter\HasConstructableReporter;

#[AsReporter]
final class ReportEventStub implements Reporter
{
    use HasConstructableReporter;

    public function relay(object|array $message): void
    {
        $this->dispatch($message);
    }
}
