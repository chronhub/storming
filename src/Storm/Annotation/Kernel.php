<?php

declare(strict_types=1);

namespace Storm\Annotation;

use Illuminate\Contracts\Foundation\Application;
use Storm\Aggregate\Attribute\AggregateRepositoryMap;
use Storm\Chronicler\Attribute\ChroniclerMap;
use Storm\Chronicler\Attribute\Subscriber\StreamSubscriberMap;
use Storm\Message\Attribute\MessageMap;
use Storm\Reporter\Attribute\ReporterMap;
use Storm\Reporter\Attribute\Subscriber\SubscriberMap;

class Kernel
{
    public static bool $loaded = false;

    public function __construct(
        protected ReporterMap $reporters,
        protected SubscriberMap $subscribers,
        protected MessageMap $messages,
        protected ChroniclerMap $chroniclers,
        protected StreamSubscriberMap $streamSubscribers,
        protected AggregateRepositoryMap $aggregateRepositories,
        protected Application $app
    ) {
    }

    public function boot(): void
    {
        if (self::$loaded === true) {
            return;
        }

        $this->chroniclers->load();

        $this->aggregateRepositories->load();

        $this->streamSubscribers->load(
            $this->chroniclers->getEntries()->keys()->toArray()
        );

        $this->reporters->load();

        $this->messages->load($this->reporters->getDeclaredQueue());

        $this->subscribers->load(
            $this->reporters->getEntries()->keys()->toArray()
        );

        self::$loaded = true;
    }

    public function getStorage(): KernelStorage
    {
        return new InMemoryKernelStorage(
            $this->reporters,
            $this->subscribers,
            $this->messages,
            $this->chroniclers,
            $this->streamSubscribers,
            $this->app
        );
    }
}
