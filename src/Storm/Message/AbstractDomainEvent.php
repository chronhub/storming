<?php

declare(strict_types=1);

namespace Storm\Message;

use Storm\Contract\Message\DomainEvent;

abstract class AbstractDomainEvent implements DomainEvent
{
    use HasConstructableContent;
    use WriteHeaders;

    public function type(): DomainType
    {
        return DomainType::EVENT;
    }
}
