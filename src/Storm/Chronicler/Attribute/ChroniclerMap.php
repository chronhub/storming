<?php

declare(strict_types=1);

namespace Storm\Chronicler\Attribute;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Connection;
use Illuminate\Support\Collection;
use RuntimeException;
use Storm\Chronicler\Connection\PgsqlTransactionalChronicler;
use Storm\Chronicler\StreamListener;
use Storm\Chronicler\TrackStream;
use Storm\Chronicler\TrackTransactionalStream;
use Storm\Contract\Chronicler\Chronicler;
use Storm\Contract\Chronicler\ChroniclerDecorator;
use Storm\Contract\Tracker\StreamTracker;
use Storm\Reporter\Attribute\ReporterQueue;

use function sprintf;

class ChroniclerMap
{
    public const string ERROR_CHRONICLER_ALREADY_EXISTS = 'Chronicler %s already exists';

    /**
     * @var Collection<array<string, ChroniclerAttribute>>
     */
    protected Collection $entries;

    /**
     * @var array<ReporterQueue>
     */
    protected array $queues = [];

    public function __construct(
        protected ChroniclerLoader $loader,
        protected Application $app
    ) {
        $this->entries = new Collection();
    }

    public function load(): void
    {
        $this->loader
            ->getAttributes()
            ->each(function (ChroniclerAttribute $attribute): void {
                $this->makeEntry($attribute);

                $this->bind($attribute);
            });
    }

    public function getEntries(): Collection
    {
        return $this->entries;
    }

    protected function makeEntry(ChroniclerAttribute $attribute): void
    {
        if ($this->entries->has($attribute->abstract)) {
            throw new RuntimeException(sprintf(self::ERROR_CHRONICLER_ALREADY_EXISTS, $attribute->abstract));
        }

        if ($attribute->chronicler !== PgsqlTransactionalChronicler::class) {
            throw new RuntimeException('Chronicler class not supported yet. use reference in constructor later');
        }

        $this->entries->put($attribute->abstract, $attribute);
    }

    protected function bind(ChroniclerAttribute $attribute): void
    {
        $this->app->bind($attribute->abstract, fn (): Chronicler => $this->makeInstance($attribute));
    }

    protected function makeInstance(ChroniclerAttribute $attribute): Chronicler
    {
        $connection = $this->makeConnection($attribute->connection);
        $eventStreamProvider = $this->app[$attribute->evenStreamProvider];
        $streamPersistence = $this->app[$attribute->persistence];
        $streamEventLoader = $this->app[$attribute->streamEventLoader];
        $streamEventTable = $attribute->tableName;

        $realInstance = new $attribute->chronicler(
            $connection,
            $eventStreamProvider,
            $streamPersistence,
            $streamEventLoader,
            $streamEventTable
        );

        if (! $attribute->eventable) {
            return $realInstance;
        }

        $streamTracker = new TrackStream();
        $decoratorFactory = $this->makeDecoratorFactory($attribute->decoratorFactory);

        if ($attribute->transactional) {
            $streamTracker = new TrackTransactionalStream();
            $chroniclerDecorator = $decoratorFactory->makeTransactionalEventableChronicler($realInstance, $streamTracker);
        } else {
            $chroniclerDecorator = $decoratorFactory->makeEventableChronicler($realInstance, $streamTracker);
        }

        $subscribers = $attribute->subscribers;

        if ($subscribers === []) {
            return $chroniclerDecorator;
        }

        $this->attachSubscribers($realInstance, $streamTracker, $subscribers);

        return $chroniclerDecorator;
    }

    protected function attachSubscribers(Chronicler $chronicler, StreamTracker $streamTracker, array $subscribers): void
    {
        $realInstance = $this->getRealInstance($chronicler);

        foreach ($subscribers as $subscriber) {
            $listener = $this->makeNewCallback($subscriber, $realInstance);

            $streamTracker->listen($listener);
        }
    }

    protected function makeNewCallback(string $listenerClass, Chronicler $chronicler): StreamListener
    {
        $listener = $this->app[$listenerClass];

        if (! $listener instanceof StreamListener) {
            throw new RuntimeException('Stream listener must be an instance of StreamListener');
        }

        $callback = $listener->story()($chronicler);

        return new StreamListener($listener->name(), $callback, $listener->priority());
    }

    protected function getRealInstance(Chronicler $chronicler): Chronicler
    {
        while ($chronicler instanceof ChroniclerDecorator) {
            $chronicler = $chronicler->innerChronicler();
        }

        return $chronicler;
    }

    protected function makeDecoratorFactory(string $decoratorFactory): ChroniclerDecoratorFactory
    {
        return $this->app[$decoratorFactory];
    }

    protected function makeConnection(string $connection): Connection
    {
        return $this->app['db']->connection($connection);
    }
}
