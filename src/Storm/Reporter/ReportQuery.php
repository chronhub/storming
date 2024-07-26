<?php

declare(strict_types=1);

namespace Storm\Reporter;

use React\Promise\PromiseInterface;
use Storm\Contract\Reporter\Reporter;
use Storm\Message\DomainType;
use Storm\Reporter\Attribute\AsReporter;
use Storm\Reporter\Attribute\Mode;

#[AsReporter(
    id: 'reporter.query.default',
    type: DomainType::QUERY,
    mode: Mode::SYNC,
)]
final class ReportQuery implements Reporter
{
    use HasConstructableReporter;

    public function relay(object|array $message): PromiseInterface
    {
        $story = $this->dispatch($message);

        return $story->promise();
    }
}
