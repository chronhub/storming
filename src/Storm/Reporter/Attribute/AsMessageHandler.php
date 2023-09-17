<?php

declare(strict_types=1);

namespace Storm\Reporter\Attribute;

use Attribute;
use InvalidArgumentException;
use Storm\Attribute\Definition\MessageDeclarationScope;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final readonly class AsMessageHandler
{
    public function __construct(
        /**
         * todo
         * Override reporter producer behavior when async/per message
         * A sync reporter can not be overridden
         */
        public ?string $producer = null,

        /**
         * __invoke method is forbidden when target method is used
         */
        public string $method = '__invoke',

        /**
         * Required when target method is used
         *
         * @param int<0, max> $priority
         */
        public int $priority = 0,

        /**
         * Determine where message can be declared
         *
         * @param MessageDeclarationScope::Unique|MessageDeclarationScope::BelongsToClass|MessageDeclarationScope::BelongsToMany $scope
         */
        public MessageDeclarationScope $scope = MessageDeclarationScope::Unique,
    ) {
        if ($this->priority < 0) {
            throw new InvalidArgumentException('Priority must be greater than or equal to 0');
        }
    }
}
