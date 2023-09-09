<?php

declare(strict_types=1);

namespace Storm\Reporter;

use Storm\Contract\Reporter\Reporter;
use Storm\Reporter\Attribute\AsReporter;
use Storm\Reporter\Filter\AllowsAnyCommand;

#[AsReporter(name: 'reporter-command-default', filter: new AllowsAnyCommand())]
final class ReportCommand implements Reporter
{
    use DelegateToQueue;
    use HasConstructableReporter;

    public function relay(object|array $message): void
    {
        $this->queueAndProcess($message);
    }
}
