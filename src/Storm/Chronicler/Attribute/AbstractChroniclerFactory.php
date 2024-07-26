<?php

declare(strict_types=1);

namespace Storm\Chronicler\Attribute;

use Illuminate\Contracts\Foundation\Application;
use RuntimeException;
use Storm\Chronicler\EventChronicler;
use Storm\Chronicler\StreamListener;
use Storm\Chronicler\TrackStream;
use Storm\Chronicler\TrackTransactionalStream;
use Storm\Chronicler\TransactionalEventChronicler;
use Storm\Contract\Chronicler\Chronicler;
use Storm\Contract\Chronicler\ChroniclerDecorator;
use Storm\Contract\Chronicler\EventableChronicler;
use Storm\Contract\Chronicler\TransactionalEventableChronicler;
use Storm\Contract\Tracker\StreamTracker;
use Storm\Contract\Tracker\TransactionalStreamTracker;

use function sprintf;

abstract class AbstractChroniclerFactory
{
    protected function createDecoratedInstance(Chronicler $instance, bool $transactional, array $subscribers): ChroniclerDecorator
    {
        $streamTracker = $transactional ? new TrackTransactionalStream() : new TrackStream();
        $decoratedInstance = $transactional
            ? $this->withEventAndTransaction($instance, $streamTracker)
            : $this->withEvent($instance, $streamTracker);

        if ($subscribers !== []) {
            $this->attachSubscribers($instance, $streamTracker, $subscribers);
        }

        return $decoratedInstance;
    }

    protected function withEvent(Chronicler $realInstance, StreamTracker $streamTracker): EventableChronicler
    {
        return new EventChronicler($realInstance, $streamTracker);
    }

    protected function withEventAndTransaction(Chronicler $realInstance, TransactionalStreamTracker $streamTracker): TransactionalEventableChronicler
    {
        return new TransactionalEventChronicler($realInstance, $streamTracker);
    }

    protected function attachSubscribers(Chronicler $chronicler, StreamTracker $streamTracker, array $subscribers): void
    {
        $realInstance = $this->getRealInstance($chronicler);

        // note that subscribers are attached to the tracker, not the chronicler
        foreach ($subscribers as $subscriber) {
            $streamTracker->listen(
                $this->makeNewCallback($subscriber, $realInstance)
            );
        }
    }

    protected function makeNewCallback(string $listenerClass, Chronicler $chronicler): StreamListener
    {
        $listener = $this->app()[$listenerClass];

        if (! $listener instanceof StreamListener) {
            throw new RuntimeException(sprintf(
                'Stream listener class %s must be an instance of %s',
                $listenerClass,
                StreamListener::class
            ));
        }

        return new StreamListener($listener->name(), $listener->story()($chronicler), $listener->priority());
    }

    protected function getRealInstance(Chronicler $chronicler): Chronicler
    {
        while ($chronicler instanceof ChroniclerDecorator) {
            $chronicler = $chronicler->innerChronicler();
        }

        return $chronicler;
    }

    abstract protected function app(): Application;
}
