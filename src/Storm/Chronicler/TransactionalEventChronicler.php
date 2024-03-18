<?php

declare(strict_types=1);

namespace Storm\Chronicler;

use Storm\Chronicler\Exceptions\RuntimeException;
use Storm\Contract\Chronicler\TransactionalChronicler;
use Storm\Contract\Chronicler\TransactionalEventableChronicler;
use Storm\Contract\Tracker\TransactionalStreamStory;
use Throwable;

final readonly class TransactionalEventChronicler extends EventChronicler implements TransactionalEventableChronicler
{
    public function beginTransaction(): void
    {
        /** @var TransactionalStreamStory $story */
        $story = $this->streamTracker->newStory(self::BEGIN_TRANSACTION_EVENT);

        $this->streamTracker->disclose($story);

        if ($story->hasTransactionAlreadyStarted()) {
            throw $story->exception();
        }
    }

    public function commitTransaction(): void
    {
        $story = $this->streamTracker->newStory(self::COMMIT_TRANSACTION_EVENT);

        $this->streamTracker->disclose($story);

        /** @var TransactionalStreamStory $story */
        if ($story->hasTransactionNotStarted()) {
            throw $story->exception();
        }
    }

    public function rollbackTransaction(): void
    {
        $story = $this->streamTracker->newStory(self::ROLLBACK_TRANSACTION_EVENT);

        $this->streamTracker->disclose($story);

        /** @var TransactionalStreamStory $story */
        if ($story->hasTransactionNotStarted()) {
            throw $story->exception();
        }
    }

    public function transactional(callable $callback): bool|array|string|int|float|object
    {
        $this->beginTransaction();

        try {
            $result = $callback($this);

            $this->commitTransaction();

            return $result === null ? true : $result;
        } catch (Throwable $exception) {
            $this->rollbackTransaction();

            throw $exception;
        }
    }

    public function inTransaction(): bool
    {
        if ($this->innerChronicler() instanceof TransactionalChronicler) {
            return $this->innerChronicler()->inTransaction();
        }

        throw new RuntimeException('Inner chronicler is not a transactional chronicler');
    }
}
