<?php

declare(strict_types=1);

namespace Storm\Reporter\Subscriber;

use Closure;
use Storm\Annotation\Reference\Reference;
use Storm\Contract\Chronicler\Chronicler;
use Storm\Contract\Chronicler\TransactionalEventableChronicler;
use Storm\Contract\Reporter\Reporter;
use Storm\Contract\Tracker\MessageStory;
use Storm\Reporter\Attribute\Subscriber\AsReporterSubscriber;

final readonly class TransactionalCommand
{
    public function __construct(#[Reference('chronicler.event.transactional.standard.pgsql')] private Chronicler $chronicler)
    {
    }

    #[AsReporterSubscriber(
        supports: ['reporter.command.default'],
        event: Reporter::DISPATCH_EVENT,
        method: 'startTransaction',
        priority: 30000,
        autowire: true,
    )]
    public function startTransaction(): Closure
    {
        return function (): void {
            if ($this->chronicler instanceof TransactionalEventableChronicler) {
                $this->chronicler->beginTransaction();
            }
        };
    }

    #[AsReporterSubscriber(
        supports: ['reporter.command.default'],
        event: Reporter::FINALIZE_EVENT,
        method: 'finalizeTransaction',
        priority: 100,
        autowire: true,
    )]
    public function finalizeTransaction(): Closure
    {
        return function (MessageStory $story): void {
            if (! $this->chronicler instanceof TransactionalEventableChronicler) {
                return;
            }

            $story->hasException()
                ? $this->chronicler->rollbackTransaction()
                : $this->chronicler->commitTransaction();
        };
    }
}
