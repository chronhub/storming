<?php

declare(strict_types=1);

namespace Storm\Message;

use Storm\Contract\Message\DomainQuery as Query;

abstract class DomainQuery implements Query
{
    use HasConstructableContent;
    use WriteHeaders;
}
