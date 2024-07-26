<?php

declare(strict_types=1);

namespace Storm\Message\Attribute;

use Attribute;
use Storm\Message\DomainType;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class AsEventHandler extends AsMessageHandler
{
    public function __construct(
        string $reporter,
        string $handles,
        string|array|null $fromQueue = null,
        ?string $method = null,
        int $priority = 0,
    ) {
        parent::__construct(
            $reporter,
            $handles,
            $fromQueue,
            $method,
            $priority,
        );
    }

    public function type(): DomainType
    {
        return DomainType::EVENT;
    }
}
