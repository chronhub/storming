<?php

declare(strict_types=1);

namespace Storm\Chronicler\Attribute;

use Attribute;
use RuntimeException;
use Storm\Chronicler\Connection\ConnectionEventStreamProvider;
use Storm\Chronicler\Connection\CursorConnectionLoader;
use Storm\Chronicler\Connection\StandardStreamPersistence;
use Storm\Chronicler\StreamListener;

use function sprintf;

#[Attribute(Attribute::TARGET_CLASS)]
class AsChronicler
{
    public function __construct(
        public string $connection,
        public string $abstract,
        public string $persistence = StandardStreamPersistence::class,
        public string $evenStreamProvider = ConnectionEventStreamProvider::class,
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
        if ($this->firstClass === $this->abstract) {
            throw new RuntimeException(sprintf(
                'First class cannot be the same as the chronicler class %s',
                $this->abstract
            ));
        }
    }
}
