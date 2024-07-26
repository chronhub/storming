<?php

declare(strict_types=1);

namespace Storm\Chronicler\Subscriber;

use Closure;
use Storm\Chronicler\Attribute\Subscriber\AsStreamSubscriber;
use Storm\Chronicler\Exceptions\TransactionAlreadyStarted;
use Storm\Contract\Chronicler\TransactionalChronicler;
use Storm\Contract\Chronicler\TransactionalEventableChronicler;
use Storm\Contract\Tracker\TransactionalStreamStory;

#[AsStreamSubscriber(
    event: TransactionalEventableChronicler::BEGIN_TRANSACTION_EVENT,
    chronicler: 'chronicler.event.transactional.*'
)]
final class BeginTransaction
{
    public function __invoke(TransactionalChronicler $chronicler): Closure
    {
        return static function (TransactionalStreamStory $story) use ($chronicler): void {
            try {
                $chronicler->beginTransaction();
            } catch (TransactionAlreadyStarted $exception) {
                $story->withRaisedException($exception);
            }
        };
    }
}
