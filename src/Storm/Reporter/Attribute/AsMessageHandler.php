<?php

declare(strict_types=1);

namespace Storm\Reporter\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final readonly class AsMessageHandler
{
    public function __construct(
        /**
         * Override reporter producer behavior when async/per message
         * A sync reporter can not be overridden
         */
        public ?string $producer = null,

        /**
         * __invoke for target class but disallowed for target method
         * Required for target method
         */
        public ?string $method = null,

        /**
         * Required when target method is used
         */
        public int $priority = 0
    ) {
    }
}
