<?php

declare(strict_types=1);

namespace Storm\Chronicler\Attribute;

use Attribute;
use Storm\Chronicler\Connection\CursorConnectionLoader;
use Storm\Chronicler\Connection\EventStreamProvider;
use Storm\Chronicler\Connection\StandardStreamPersistence;
use Storm\Chronicler\StreamListener;

#[Attribute(Attribute::TARGET_CLASS)]
class AsChronicler
{
    public function __construct(
        public string $connection,
        public string $abstract,
        public string $persistence = StandardStreamPersistence::class,
        public string $evenStreamProvider = EventStreamProvider::class,
        public string $streamEventLoader = CursorConnectionLoader::class,
        public bool $eventable = true,
        public bool $transactional = true,
        public string $factory = ChroniclerConnectionFactory::class,
        /**
         * Dedicate subscribers to a specific event store
         * stream subscribers attribute will be ignored
         *
         * @var array<StreamListener>
         */
        public array $subscribers = [],
        public string $tableName = 'stream_event',// todo: move to config
        /**
         * First class
         */
        public ?string $firstClass = null,
    ) {
    }
}
