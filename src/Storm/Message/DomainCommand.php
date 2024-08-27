<?php

declare(strict_types=1);

namespace Storm\Message;

use Storm\Contract\Message\DomainCommand as Command;

abstract class DomainCommand implements Command
{
    use HasConstructableContent;
    use WriteHeaders;
}
