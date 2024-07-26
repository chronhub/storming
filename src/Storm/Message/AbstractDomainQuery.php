<?php

declare(strict_types=1);

namespace Storm\Message;

use Storm\Contract\Message\DomainQuery;

abstract class AbstractDomainQuery implements DomainQuery
{
    use HasConstructableContent;
    use WriteHeaders;

    public function type(): DomainType
    {
        return DomainType::QUERY;
    }
}
