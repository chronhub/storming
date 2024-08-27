<?php

declare(strict_types=1);

namespace Storm\Story\Attribute;

use Attribute;
use Storm\Story\DefaultStoryContext;
use Storm\Story\StoryContext;

#[Attribute(Attribute::TARGET_CLASS)]
class AsQueryHandler
{
    public function __construct(

        /**
         * The query class name that this handler handles.
         *
         * @var string
         */
        public ?string $handles = null,

        /**
         * The method to be called when this event handler is triggered.
         *
         * @var string
         */
        public string $method = '__invoke',

        /**
         * The middleware context to be used when calling the handler.
         *
         *  When middleware string set, it is resolved through the container,
         *  and must return a JsonSerializable, Arrayable instance or callable iterable.
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
         * Configure yourself a story context,
         * It must be bound to the container.
         *
         * @var string|null
         */
        public ?string $contextId = null,
    ) {}
}
