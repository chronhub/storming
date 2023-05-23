<?php

declare(strict_types=1);

namespace Storm\Message;

use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Message\DomainType;

abstract class AbstractDomainEvent implements DomainEvent
{
    use HasConstructableContent;
    use WriteHeaders;

    public function supportType(): string
    {
        return DomainType::EVENT;
    }
}
