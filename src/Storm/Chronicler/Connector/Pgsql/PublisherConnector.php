<?php

declare(strict_types=1);

namespace Storm\Chronicler\Connector\Pgsql;

use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Queue\Queueable;
use Storm\Chronicler\Connector\ConnectionManager;
use Storm\Chronicler\Connector\Connector;
use Storm\Chronicler\Exceptions\InvalidArgumentException;
use Storm\Chronicler\Tracker\PublishStreamEventOnAppend;
use Storm\Chronicler\Tracker\Tracker;
use Storm\Contract\Chronicler\DatabaseChronicler;

use function class_exists;
use function class_uses;
use function in_array;
use function is_string;

final readonly class PublisherConnector implements Connector
{
    public function __construct(private Application $app) {}

    public function connect(array $config): ConnectionManager
    {
        $connector = $this->app[PgsqlConnector::class];

        $manager = $connector->connect($config);
        $eventStore = $manager->create();

        if (! $eventStore instanceof DatabaseChronicler) {
            throw new InvalidArgumentException('The event store must be a database chronicler');
        }

        return new PublisherConnection(
            $eventStore,
            new Tracker,
            $this->publisherSubscriber($config),
        );
    }

    private function publisherSubscriber(array $config): callable
    {
        $queue = $config['queue'];
        $job = $queue['job'] ?? null;

        if (! is_string($job) || ! class_exists($job) || ! in_array(Queueable::class, class_uses($job))) {
            throw new InvalidArgumentException('Invalid job class');
        }

        return new PublishStreamEventOnAppend(
            $this->app[Dispatcher::class],
            $job,
            $queue['connection'] ?? null,
            $queue['queue'] ?? null,
        );
    }
}
