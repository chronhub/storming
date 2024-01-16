<?php

declare(strict_types=1);

namespace Storm\Reporter;

use Storm\Contract\Reporter\Reporter;

final class ReportEvent implements Reporter
{
    use HasConstructableReporter;

    public function relay(object|array $message): void
    {
        $this->dispatch($message);
    }
}
