<?php

declare(strict_types=1);

namespace Storm\Message;

use Storm\Contract\Message\DomainQuery;
use Storm\Contract\Message\DomainType;

abstract class AbstractDomainQuery implements DomainQuery
{
    use HasConstructableContent;
    use WriteHeaders;

    public function supportType(): string
    {
        return DomainType::QUERY;
    }
}
