<?php

declare(strict_types=1);

namespace Storm\Chronicler\Connector\Pgsql;

use Storm\Chronicler\Connector\ConnectionManager;
use Storm\Chronicler\Database\PublisherEventStore;
use Storm\Chronicler\Tracker\StreamTracker;
use Storm\Contract\Chronicler\DatabaseChronicler;
use Storm\Contract\Chronicler\EventableTransactionalChronicler;

final class PublisherConnection implements ConnectionManager
{
    private $publisherSubscriber;

    public function __construct(
        private readonly DatabaseChronicler $chronicler,
        private readonly StreamTracker $tracker,
        callable $publisherSubscriber,
    ) {
        $this->publisherSubscriber = $publisherSubscriber;
    }

    public function create(): EventableTransactionalChronicler
    {
        return new PublisherEventStore(
            $this->chronicler,
            $this->tracker,
            $this->publisherSubscriber,
        );
    }
}
