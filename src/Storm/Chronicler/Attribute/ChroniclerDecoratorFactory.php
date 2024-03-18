<?php

declare(strict_types=1);

namespace Storm\Chronicler\Attribute;

use Storm\Chronicler\EventChronicler;
use Storm\Chronicler\TransactionalEventChronicler;
use Storm\Contract\Chronicler\Chronicler;
use Storm\Contract\Chronicler\EventableChronicler;
use Storm\Contract\Chronicler\TransactionalEventableChronicler;
use Storm\Contract\Tracker\StreamTracker;
use Storm\Contract\Tracker\TransactionalStreamTracker;

class ChroniclerDecoratorFactory
{
    public function makeEventableChronicler(
        Chronicler $realInstance,
        StreamTracker $streamTracker
    ): EventableChronicler {
        return new EventChronicler($realInstance, $streamTracker);
    }

    public function makeTransactionalEventableChronicler(
        Chronicler $realInstance,
        TransactionalStreamTracker $streamTracker
    ): TransactionalEventableChronicler {
        return new TransactionalEventChronicler($realInstance, $streamTracker);
    }
}
