<?php

declare(strict_types=1);

namespace Storm\Story\Attribute;

use Attribute;
use Storm\Message\Decorator\EventType;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
readonly class AsHeader
{
    public function __construct(

        /**
         * A Service ID of a (chain) Message Decorator.
         * It must provide an event type, event id and event time at least.
         *
         * @see EventType
         * @see EventSymfonyId
         * @see EventTime
         *
         * The default implementation should be configured from your [storm] config.
         */
        public ?string $default = null,

        /**
         * A list of service IDs of Message Decorators to enrich a specific message.
         *
         * @var array<string>
         */
        public array $decorators = [],
    ) {}
}
