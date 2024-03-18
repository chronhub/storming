<?php

declare(strict_types=1);

namespace Storm\Chronicler\Subscriber;

use Closure;
use Storm\Chronicler\Attribute\Subscriber\AsStreamSubscriber;
use Storm\Chronicler\Exceptions\TransactionNotStarted;
use Storm\Contract\Chronicler\TransactionalChronicler;
use Storm\Contract\Chronicler\TransactionalEventableChronicler;
use Storm\Contract\Tracker\StreamStory;
use Storm\Contract\Tracker\TransactionalStreamStory;

#[AsStreamSubscriber(
    event: TransactionalEventableChronicler::ROLLBACK_TRANSACTION_EVENT,
    chronicler: 'chronicler.event.transactional.*'
)]
final class RollbackTransaction
{
    public function __invoke(TransactionalChronicler $chronicler): Closure
    {
        return static function (TransactionalStreamStory|StreamStory $story) use ($chronicler): void {
            try {
                $chronicler->rollbackTransaction();
            } catch (TransactionNotStarted $exception) {
                $story->withRaisedException($exception);
            }
        };
    }
}
