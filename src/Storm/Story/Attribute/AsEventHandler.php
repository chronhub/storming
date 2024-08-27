<?php

declare(strict_types=1);

namespace Storm\Story\Attribute;

use Attribute;
use Illuminate\Contracts\Support\Arrayable;
use Storm\Story\DefaultStoryContext;
use Storm\Story\StoryContext;

/**
 * The event handler is unique in that multiple handlers can exist for a single event.
 * To streamline the process, only the highest and unique priority handler is permitted to define
 * the queue, middleware, and contextId.
 * Typically, there is a single handler that manages the entire dispatch process from
 * within the same class.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class AsEventHandler
{
    public function __construct(
        /**
         * The event class name that this handler handles.
         *
         * @var null|class-string
         */
        public ?string $handles = null,

        /**
         * The method to be called when this event handler is triggered.
         *
         * @var string
         */
        public string $method = '__invoke',

        /**
         * The queue options to be merged to a message job.
         *
         * When queue string set, it is resolved through the container,
         * and must return a JsonSerializable, Arrayable instance or callable iterable.
         *
         * @var string|array<string, mixed>
         */
        public string|array $queue = [],

        /**
         * Sorted by priority in descending order.
         *
         * @var int
         */
        public int $priority = 0,

        /**
         * The middleware context to be used when calling the handler.
         *
         * When middleware string set, it is resolved through the container,
         * and must return a JsonSerializable, Arrayable instance or callable iterable.
         *
         * @see StoryContext
         * @see DefaultStoryContext
         *
         * @var string|array<string>
         */
        public string|array $middleware = [],

        /**
         * The story context ID to be used when calling the handler.
         *
         * Configure yourself a story context which can be resolved from ioc.
         *
         * @var string|null
         */
        public ?string $contextId = null,
    ) {}
}
