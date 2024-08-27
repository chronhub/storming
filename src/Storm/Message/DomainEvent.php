<?php

declare(strict_types=1);

namespace Storm\Message;

use Storm\Contract\Message\DomainEvent as Event;

abstract class DomainEvent implements Event
{
    use HasConstructableContent;
    use WriteHeaders;
}
