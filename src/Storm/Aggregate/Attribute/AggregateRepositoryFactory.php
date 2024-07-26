<?php

declare(strict_types=1);

namespace Storm\Aggregate\Attribute;

use Storm\Aggregate\AggregateEventReleaser;
use Storm\Aggregate\GenericAggregateRepository;
use Storm\Contract\Aggregate\AggregateRepository;
use Storm\Contract\Chronicler\Chronicler;
use Storm\Contract\Message\MessageDecorator;
use Storm\Message\NoOpMessageDecorator;
use Storm\Stream\StreamName;

class AggregateRepositoryFactory
{
    public function makeRepository(Chronicler $chronicler, StreamName $streamName, ?MessageDecorator $messageDecorator = null): AggregateRepository
    {
        $messageDecorator ??= new NoOpMessageDecorator();

        $eventReleaser = new AggregateEventReleaser($messageDecorator);

        return new GenericAggregateRepository($chronicler, $streamName, $eventReleaser);
    }
}
