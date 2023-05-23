<?php

declare(strict_types=1);

namespace Storm\Message;

use Storm\Contract\Message\DomainCommand;
use Storm\Contract\Message\DomainType;

abstract class AbstractDomainCommand implements DomainCommand
{
    use HasConstructableContent;
    use WriteHeaders;

    public function supportType(): string
    {
        return DomainType::COMMAND;
    }
}
