<?php

declare(strict_types=1);

namespace Storm\Message\Attribute;

use Attribute;
use Storm\Message\DomainType;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class AsQueryHandler extends AsMessageHandler
{
    public function __construct(
        string $reporter,
        string $handles,
        string|array|null $fromQueue = null,
        ?string $method = null,
    ) {
        parent::__construct(
            $reporter,
            $handles,
            $fromQueue,
            $method,
        );
    }

    public function type(): DomainType
    {
        return DomainType::QUERY;
    }
}
