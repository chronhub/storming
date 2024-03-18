<?php

declare(strict_types=1);

namespace Storm\Reporter;

use Storm\Contract\Reporter\Reporter;
use Storm\Message\DomainType;
use Storm\Reporter\Attribute\AsReporter;
use Storm\Reporter\Attribute\Mode;

#[AsReporter(
    id: 'reporter.event.default',
    type: DomainType::EVENT,
    mode: Mode::SYNC,
)]
final class ReportEvent implements Reporter
{
    use HasConstructableReporter;

    public function relay(object|array $message): void
    {
        $this->dispatch($message);
    }
}
