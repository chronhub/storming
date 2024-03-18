<?php

declare(strict_types=1);

namespace Storm\Reporter;

use Storm\Contract\Reporter\Reporter;
use Storm\Message\DomainType;
use Storm\Reporter\Attribute\AsReporter;
use Storm\Reporter\Attribute\Mode;
use Storm\Reporter\Producer\QueueOption;

#[AsReporter(
    id: 'reporter.command.default',
    type: DomainType::COMMAND,
    mode: Mode::ASYNC,
    defaultQueue: QueueOption::class
)]
final class ReportCommand implements Reporter
{
    use DelegateToQueue;
    use HasConstructableReporter;

    public function relay(object|array $message): void
    {
        $this->queueAndProcess($message);
    }
}
